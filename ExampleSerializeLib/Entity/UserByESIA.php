<?php

namespace App\Entity;

use App\Model\ESIA\PersonData;
use App\Repository\UserByESIARepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

/**
 * @ORM\Entity(repositoryClass=UserByESIARepository::class)
 */
class UserByESIA extends User
{

    const GROUP_SERIALIZE_SHORT=['id', 'class', 'phone', 'email', 'name','surname','patronymic','ESIAId' ];
    const GROUP_SERIALIZE_STAGE =['id', 'class', 'phone', 'email', 'name','surname','patronymic',
        'createDateTime', 'editDateTime', 'lastActivityDateTime'
        ,'ESIAId'];

    /**
     * @ORM\Column(type="integer",nullable=false,unique=true)
     * @var int
     */
    protected $ESIAId;


    /**
     * @return int
     */
    public function getESIAId():int
    {
        return $this->ESIAId;
    }

    public function setESIAId(int $ESIAId): self
    {
        $this->ESIAId = $ESIAId;

        return $this;
    }

    /**
     * @param PersonData $PersonData
     * @return $this
     */
    public function fillPersonData(PersonData $PersonData): self
    {

        $this->setName($PersonData->firstName);
        $this->setSurname($PersonData->lastName);
        $this->setPatronymic($PersonData->middleName);
        if (empty($this->getCreateDateTime())) {
            $this->setCreateDateTime(new \DateTime());
        }
        return $this;
//        $this->setPhone();
//        $this->setEmail();

    }

    /**
     * @param $SourceArray
     * @return User
     */
    public function fillByApiArray($SourceArray): User
    {
        if(isset($SourceArray['phone'])){
            $this->setPhone($SourceArray['phone']);
        }
        if(isset($SourceArray['email'])){
            $this->setEmail($SourceArray['email']);
        }
        return $this;
    }
}
