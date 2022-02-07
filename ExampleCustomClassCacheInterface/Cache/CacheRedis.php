<?php

namespace App\Cache;

use Predis\ClientInterface as ClientInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface as CacheInterface;

class CacheRedis implements CacheInterface
{
    const LOCK_WRITE_PREFIX = 'CacheRedis_write_lock_';
    /**
     * @var  LockFactory
     *
     */
    protected $LockFactory;
    /**
     * @var  ClientInterface
     */
    protected $Redis;

    /**
     * @var bool
     */
    protected $isUseLock;
    /**
     * @var integer
     */
    protected $waitValue;


    public function __construct(ClientInterface $Redis, LockFactory $LockFactory)
    {
        $this->LockFactory = $LockFactory;
        $this->Redis = $Redis;
    }

    /**
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return void
     */
    public function setValue(string $key, $value, int $ttl)
    {
        $newCacheItem = new CacheItem($key);
        $newCacheItem->set($value)
            ->expiresAfter($ttl);
        $this->saveCacheItem($newCacheItem);
    }


    public function hasItem($key): bool
    {
        return (boolean)$this->Redis->exists($key);
    }

    private function saveCacheItem(CacheItem $CacheItem)
    {
        $ttlModify = $CacheItem->getRedisExpiraionModify();
        $ttlValue = $CacheItem->getRedisExpireValue();
        if ($ttlModify) {
            $this->Redis->set($CacheItem->getKey(), $CacheItem->getValueSerialize(), $ttlModify, $ttlValue);
        } else {
            $this->Redis->set($CacheItem->getKey(), $CacheItem->getValueSerialize());
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null)
    {
        if ($this->hasItem($key)) {
            $CacheItem = new CacheItem($key, $this->Redis->get($key), true);
            return $CacheItem->get();
        }
        $newCacheItem = new CacheItem($key);
        $isSaveValue = true;
        $value = $callback($newCacheItem, $isSaveValue);
        $newCacheItem->set($value);
        if ($isSaveValue) {
            $this->saveCacheItem($newCacheItem);
        }
        return $value;
    }

    public function getConcurrentPending(string $key, callable $callback, $limitTTLWriteLock = 0, $limitWaitingForParallelReceipt = 0)
    {
        if ($this->hasItem($key)) {
            $CacheItem = new CacheItem($key, $this->Redis->get($key), true);
            return $CacheItem->get();
        }

        $newCacheItem = new CacheItem($key);
        $isSaveValue = true;
        $lockWrite = $this->LockFactory->createLock(self::LOCK_WRITE_PREFIX . $key, $limitTTLWriteLock);

        if ($lockWrite->acquire()) {
            $value = $callback($newCacheItem, $isSaveValue);
            $newCacheItem->set($value);
            /**
             * if the lock has not expired.
             * And the lock is still owned by the current process.
             * write to cache
             */
            if ($lockWrite->isAcquired() && $isSaveValue) {
                $this->saveCacheItem($newCacheItem);
            } elseif (!$this->hasItem($key) && $isSaveValue) {
                /**
                 *trying to re-acquire the lock.
                 * if it is not occupied by another process, we still save the data to the cache
                 */
                $lockWrite->release();
                if ($lockWrite->acquire()) {
                            $this->saveCacheItem($newCacheItem);
                }
            }
            $lockWrite->release();
        } else {
            $startLimit = $limitWaitingForParallelReceipt;
            $isValueReceived = false;
            while ($limitWaitingForParallelReceipt > 0) {
                /**
                 * wait 0.3 seconds.
                 */
                $this->nanoSleep(0.3);
                $limitWaitingForParallelReceipt = $limitWaitingForParallelReceipt - 0.3;
                /**
                 * And we check if the value has appeared in the cache
                 */
                if ($this->hasItem($key)) {
                    $CacheItem = new CacheItem($key, $this->Redis->get($key), true);
                    $value = $CacheItem->get();
                    $isValueReceived = true;
                    break;
                }
            }
            /**
             * if the waiting limit has expired, execute the callback and value not Received
             */
            if (!$isValueReceived) {
                /**
                 * If the wait was not null. Trying to write the cache
                 */
                if ($startLimit > 0 && $lockWrite->acquire()) {
                    $value = $callback($newCacheItem, $isSaveValue);
                    /**
                     * if the lock has not expired.
                     * And the lock is still owned by the current process.
                     * write to cache
                     */
                    if ($lockWrite->isAcquired()) {
                        $newCacheItem->set($value);
                        if ($isSaveValue) {
                            $this->saveCacheItem($newCacheItem);
                        }
                    }
                    $lockWrite->release();
                } else {
                    $value = $callback($newCacheItem, $isSaveValue);
                }
            }
        }
        return $value;
    }

//private function isNotMoreRelevantData($key,$ttl)
//{ $isInCache=$this->hasItem($key);
//    if(!$isInCache){
//        return true;
//    }else{
//        $TTLCurrentItem=$this->Redis->ttl($key);
//        $TTLCurrentItem
//
//
//    }
//
//
//}
    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->Redis->del($key);
        return true;
    }

    private function nanoSleep(float $secondFloat)
    {
        $secondInteger = floor($secondFloat);
        $nanoSecond = fmod($secondFloat, 1) * 1000000000;
        time_nanosleep($secondInteger, $nanoSecond);
    }
}