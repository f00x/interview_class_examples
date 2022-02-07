<?php

namespace App\Cache;

use DateTime as DateTime;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;

class CacheItem implements ItemInterface
{
    const REDIS_EXPIRATION_MODIFY_SECOND_TTL='EX';
    const REDIS_EXPIRATION_MODIFY_SECOND_TIMESTAMP='EXAT';
    /**
     * @var ?string
     */
    protected $valueSerialize = "";

    /**
     * @var string
     */
    protected $key;

    /**
     * isCacheItemFind
     * @var string
     */
    protected $isHit = false;
    /**
     * (EXAT) specified Unix time at which the key will expire, in seconds.
     * @var ?DateTime
     */
    protected $expiresDateTime;

    /**
     *    EX seconds -- Set the specified expire time, in seconds.
     * @var ?int
     */
    protected $expiresSecond;

    /**
     * @param string|null $valueSerialize
     * @param string $key
     * @param bool|string $isHit
     */
    public function __construct(string $key, ?string $valueSerialize=null,  $isHit=false)
    {
        $this->valueSerialize = $valueSerialize;
        $this->key = $key;
        $this->isHit = $isHit;
    }

    public function getRedisExpiraionModify():?string
    {
        if(!is_null($this->expiresSecond))
        {
            return self::REDIS_EXPIRATION_MODIFY_SECOND_TTL;
        }elseif(!is_null($this->expiresDateTime))
        {
            return self::REDIS_EXPIRATION_MODIFY_SECOND_TIMESTAMP;
        }
        return null;
    }
    public function getRedisExpireValue():?int
    {
        if(!is_null($this->expiresSecond))
        {
            return $this->expiresSecond;
        }elseif(!is_null($this->expiresDateTime))
        {
            return $this->expiresDateTime->getTimestamp();
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getValueSerialize(): ?string
    {
        return $this->valueSerialize;
    }


    /**
     * @return DateTime|null
     */
    public function getExpiresDateTime(): ?DateTime
    {
        return $this->expiresDateTime;
    }

    /**
     * @return int|null
     */
    public function getExpiresSecond(): ?int
    {
        return $this->expiresSecond;
    }




    /**
     * @inheritDoc
     */
    public function getKey():string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        if(is_string($this->valueSerialize)) {
            return unserialize($this->valueSerialize);
        }else{
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function isHit():bool
    {
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function set($value):self
    {
        $this->valueSerialize = serialize($value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration):self
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expiresDateTime = $expiration;
        } else {
            $this->expiresDateTime = null;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time=10):self
    {
        if($time instanceof \DateInterval)
        { //convert to seconds
            $seconds = date_create('@0')->add($time)->getTimestamp();
            $this->expiresSecond= $seconds;
        }elseif (is_integer($time)){
            $this->expiresSecond=$time;
        }else{
            $this->expiresSecond=null;
        }
        return $this;
    }

    public function tag($tags): ItemInterface
    {

        return $this;
    }

    public function getMetadata(): array
    {

        return [];
    }
}