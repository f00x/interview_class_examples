<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;

abstract class AbstractStage
{
    public function __construct(array $defaultData=[])
    {
        foreach ($this->getDefaultFields() as $field) {
            if (isset($defaultData[$field])) {
                $this->{$field} = $defaultData[$field];
            }
        }
    }

    protected function getDefaultFields()
    {
        return [];
    }

    public function getDefaultDataArray()
    {
        $result = [];
        foreach ($this->getDefaultFields() as $field) {
            $result[$field] = $this->{$field};
        }

        return $result;
    }

    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return mixed
     */
    abstract public function saveDoctrine(Registry $doctrine,Advance $advance=null);
}
