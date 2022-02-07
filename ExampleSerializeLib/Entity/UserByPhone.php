<?php

namespace App\Entity;

use App\Repository\UserByPhoneRepository;
use Doctrine\ORM\Mapping as ORM;
use PlatformBundle\Entity\StatusComment;

/**
 * @ORM\Entity(repositoryClass=UserByPhoneRepository::class)
 */
class UserByPhone extends User
{

    const GROUP_SERIALIZE_SHORT  = ['id', 'class', 'phone', 'email', 'name','surname','patronymic','hash' ];
    const GROUP_SERIALIZE_PUBLIC = ['id', 'class', 'name','surname','patronymic','phone', 'email'];
    const GROUP_SERIALIZE_STAGE =['id', 'class', 'phone', 'email', 'name','surname','patronymic',
        'createDateTime', 'editDateTime', 'lastActivityDateTime','hash' ];
    protected static $groupFieldSerialize = self::GROUP_SERIALIZE_FULL;
    /**
     * @var string
     * SHA512
     * @ORM\Column(name="hash", type="string", length=128, unique=true)
     */
    private $hash;

    /**
     * @return bool
     */
    public function isFilled():bool
    {
        //&&!empty($this->getPatronymic())
        return (!empty($this->getPhone())&&!empty($this->getName())&&!empty($this->getSurname()));
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function setHash():self
    {
        if(!$this->isFilled()){
            throw new \Exception('User by phone not filed');
        }
        $keyHashing=$this->getPhone().'_'.$this->getName().'_'.$this->getSurname().'_'.$this->getPatronymic();
        $this->hash = hash( 'sha512',$keyHashing,false) ;
        return $this;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

}
