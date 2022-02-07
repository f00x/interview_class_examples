<?php


namespace App\Model;

use Doctrine\ORM\PersistentCollection;

class EntitySerialize implements \JsonSerializable, \Serializable
{
    const GROUP_SERIALIZE_ID_ONLY = ['id', 'class'];
    const GROUP_SERIALIZE_FULL = [];
    const FILTER_NONE = [];

    /** @var bool */
    private static $isShortClassName = true;
    /** @var array */
    protected static $groupFieldSerialize = self::GROUP_SERIALIZE_FULL;
    /** @var int */
    protected static $maxLevelDeepSerialize = 0;
    /** @var array */
    protected static $filterFieldSerialize = self::FILTER_NONE;
    /** @var array */
    private $groupFieldSerializeLocal;
    /** @var int */
    private $maxLevelDeepLocal;


    /**
     * @return bool
     */
    public static function isShortClassName(): bool
    {
        return self::$isShortClassName;
    }

    /**
     * @param bool $isShortClassName
     */
    public static function setIsShortClassName(bool $isShortClassName): void
    {
        self::$isShortClassName = $isShortClassName;
    }


    public function isFieldSerialize(string $varName): bool
    {
        $groupSerialize = $this->getGroupFieldSerializeLocal();
        if (empty($groupSerialize)) {
            return true;
        } else {
            return in_array($varName, $groupSerialize) || (isset($groupSerialize[$varName]) && is_array($groupSerialize[$varName]));
        }
    }

    /**
     * @return array
     */
    public function getGroupFieldSerializeLocal(): array
    {
        return empty($this->groupFieldSerializeLocal) ? self::getGroupFieldSerialize() : $this->groupFieldSerializeLocal;
    }

    /**
     * @param array $groupFieldSerializeLocal
     * @return EntitySerialize
     */
    public function setGroupFieldSerializeLocal(array $groupFieldSerializeLocal): EntitySerialize
    {
        $this->groupFieldSerializeLocal = $groupFieldSerializeLocal;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLevelDeepLocal(): int
    {
        return empty($this->maxLevelDeepLocal) ? self::getMaxLevelDeepSerialize() : $this->maxLevelDeepLocal;
    }

    /**
     * @param int $maxLevelDeepLocal
     * @return EntitySerialize
     */
    public function setMaxLevelDeepLocal(int $maxLevelDeepLocal): EntitySerialize
    {
        $this->maxLevelDeepLocal = $maxLevelDeepLocal;
        return $this;
    }


    /**
     * @return int
     */
    public static function getMaxLevelDeepSerialize(): int
    {
        return static::$maxLevelDeepSerialize;
    }

    /**
     * @param int $maxLevelDeepSerialize
     */
    public static function setMaxLevelDeepSerialize(int $maxLevelDeepSerialize): void
    {
        static::$maxLevelDeepSerialize = $maxLevelDeepSerialize;
    }


    /**
     * @return array
     */
    public static function getGroupFieldSerialize(): array
    {
        return static::$groupFieldSerialize;
    }

    /**
     * @param array $groupFieldSerialize
     */
    public static function setGroupFieldSerialize(array $groupFieldSerialize): void
    {
        static::$groupFieldSerialize = $groupFieldSerialize;
    }

    public function getArrayBase()
    {
        $obj = (array)$this;
        $baseArray = [];
        $className = false;
        $arrayFilterField = static::getFilterFieldSerialize();
        foreach ($obj as $varNameSource => $value) {
            if (strpos($varNameSource, self::class) !== false) {
                continue;
            }
            $splitName = explode("\0", $varNameSource);
            if (isset($splitName[2])) {
                if (!$className) {
                    $className = $splitName[1];
                }
                $varName = $splitName[2];
                if (!$this->isFieldSerialize($varName)) {
                    continue;
                }
                if ($value instanceof PersistentCollection && $this->getMaxLevelDeepLocal() > 0) {
                    $baseArray[$varName] = [];
                    if ($this->getMaxLevelDeepLocal() > 0) {
                        foreach ($value as $itemEntity) {
                            if ($itemEntity instanceof self) {
                                $serializeEntity = $this->serializeChild($itemEntity, $varName);
                                if (!empty($serializeEntity)) {
                                    $baseArray[$varName][] = $serializeEntity;
                                }
                            } else {
                                $baseArray[$varName][] = $itemEntity;
                            }
                        }
                    }
                } elseif ($value instanceof self && $this->getMaxLevelDeepLocal() > 0) {
                    $baseArray[$varName] = $this->serializeChild($value, $varName);
                } else {
                    $baseArray[$varName] = $value;
                }
                if (isset($arrayFilterField[$varName]) && is_callable($arrayFilterField[$varName])) {
                    //Applying an external filter
                    $baseArray[$varName] = $arrayFilterField[$varName]($baseArray[$varName]);
                }
            }
        }
        if ($this->isFieldSerialize('class')) {
            if (self::isShortClassName()) {
                $className = self::getShortClassName($className);
            }
            $baseArray = ['class' => $className] + $baseArray;
        }
        return $baseArray;
    }

    public static function getShortClassName(string $className='')
    {
        if(empty($className)){
            $className=static::class;
        }
        $splitClassName = explode('\\', $className);
        $className = array_pop($splitClassName);
        return $className;
    }

    private function serializeChild(self $child, $FieldName)
    {
        $result = null;
        $sourceGroupField = $child::getGroupFieldSerialize();
        $sourceLevel = $child::getMaxLevelDeepSerialize();
        $childGroupField = $this->getChildGroupField($FieldName);
        if (is_array($childGroupField)) {
            $child->setGroupFieldSerializeLocal($childGroupField);
        }
        if ($this->getMaxLevelDeepLocal() > 1) {
            $child->setMaxLevelDeepLocal(static::getMaxLevelDeepSerialize() - 1);
            $result = $child->serialize();
        } else if (static::getMaxLevelDeepSerialize() == 1) {
            if (!is_array($childGroupField)) {
                $child->setGroupFieldSerializeLocal(self::GROUP_SERIALIZE_ID_ONLY);
            }
            $result = $child->serialize();
        }
        $child->setMaxLevelDeepLocal($sourceLevel);
        $child->setGroupFieldSerializeLocal($sourceGroupField);
        return $result;
    }

    private function getChildGroupField($FieldName)
    {
        return (isset($this->getGroupFieldSerializeLocal()[$FieldName]) && is_array($this->getGroupFieldSerializeLocal()[$FieldName]))

            ? $this->getGroupFieldSerializeLocal()[$FieldName] : null;
    }

    /**
     * @return string
     */
    public function serialize():string
    {
        return serialize($this->getArrayBase());
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized):void
    {
        $unserializedData= unserialize($serialized);
        foreach ($this as $varName => $value) {
            if (isset($unserializedData[$varName])) {
                $this->{$varName} = $unserializedData[$varName];
            }
        }

    }

    /**
     * @return array|false[]
     */
    public function jsonSerialize()
    {
        return $this->getArrayBase();
    }

    /**
     * @return array
     */
    public static function getFilterFieldSerialize(): array
    {
        return static::$filterFieldSerialize;
    }

    /**
     * @param array $filterFieldSerialize
     */
    public static function setFilterFieldSerialize(array $filterFieldSerialize): void
    {
        static::$filterFieldSerialize = $filterFieldSerialize;
    }

}