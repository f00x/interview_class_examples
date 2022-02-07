<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use App\Entity\User;
use App\Entity\UserByESIA;
use App\Entity\UserByPhone;
use App\Repository\UserByPhoneRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use ReflectionClass as ReflectionClassAlias;
use Serializable;

class InitialUserData extends AbstractStage implements Serializable
{
    /**
     * @var User
     */
    private $User;

    /**
     * @var boolean
     */
    protected  $isErrorRecaptcha=false;

    /**
     * @var boolean
     */
    protected $isInBlackList;
    private $isAllowSendSMS=false;

    /**
     * @return bool
     */
    public function isErrorRecaptcha(): bool
    {
        return $this->isErrorRecaptcha;
    }

    /**
     * @param bool $isErrorRecaptcha
     */
    public function setIsErrorRecaptcha(bool $isErrorRecaptcha): void
    {
        $this->isErrorRecaptcha = $isErrorRecaptcha;
    }

    /**
     * InitialUserData constructor.
     * @param User $User
     */
    public function __construct(User $User)
    {
     parent::__construct([]);
        $this->User = $User;

    }


    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->User;
    }

    /**
     * @param User $User
     */
    public function setUser(User $User): void
    {
        $this->User = $User;
    }


    public function __call($name, $arguments)
    {
        if($this->isMethodUser($name)){
            return call_user_func_array(array(&$this->User, $name),$arguments);

        }

        return null;
    }

    private function isMethodUser($nameMethod)
    {
        if($this->User instanceof User){
        $rc = new ReflectionClassAlias(get_class($this->User));
        return $rc->hasMethod($nameMethod);
        }
        return false;
    }

   public function  __get($name){
    $methodName='get'.$this->camelize($name);

       if( $this->isMethodUser($methodName))
       {
           return $this->User->$methodName();
       }
       return null;
    }
    public function  __set($name,$value){
        $methodName='set'.$this->camelize($name);

        if( $this->isMethodUser($methodName))
        {
            return $this->User->$methodName($value);
        }
        return null;
    }

    /**
     * Camelizes a given string.
     */
    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }


    public function serialize()
    {

        $arrayData=[
//            'isErrorRecaptcha'=>$this->isErrorRecaptcha(),
//            'isInBlackList'=>$this->isInBlackList(),
//            'User'=>$this->getUser()
        ];

        foreach($this as $varName => $value) {
            $arrayData[$varName]=$value;
        }
        return serialize($arrayData);
    }
    public function unserialize($dataStringSerialize)
    {
        $unserializedData= unserialize($dataStringSerialize);
        foreach ($this as $varName => $value) {
            if (isset($unserializedData[$varName])) {
                $this->{$varName} = $unserializedData[$varName];
            }
        }

    }
    public function isESIAUser():bool
    {
        return ($this->User instanceof UserByESIA);
    }

    /**
     * @return bool
     */
    public function isInBlackList(): bool
    {
        return $this->isInBlackList;
    }

    /**
     * @param bool $isInBlackList
     */
    public function setIsInBlackList(bool $isInBlackList): void
    {
        $this->isInBlackList = $isInBlackList;
    }

    /**
     * @return bool
     */
    public function isAllowSendSMS(): bool
    {
        return $this->isAllowSendSMS;
    }

    /**
     * @param bool $isAllowSendSMS
     */
    public function setIsAllowSendSMS(bool $isAllowSendSMS): void
    {
        $this->isAllowSendSMS = $isAllowSendSMS;
    }

    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return $this
     * @throws \Exception
     */
    public function saveDoctrine(Registry $doctrine, Advance $advance=null,$isPassed=false):self
    {
        $manager=$doctrine->getManager();
        $User=$this->getUser();
        if(!$isPassed) {
            if ($User instanceof UserByPhone) {
                $User->setHash();
                /**
                 * @var $UserByPhoneRepository UserByPhoneRepository
                 */
                $UserByPhoneRepository = $doctrine->getRepository(UserByPhone::class);
                $User = $UserByPhoneRepository->updateOrInsert($User);
                $this->setUser($User);

            } else {
                $manager->persist($User);
                $manager->flush();
            }
        }

        if( $advance instanceof Advance && !empty($advance->getId()))
        {
            $User= $manager->getRepository(get_class($User))->find($User->getId());
            $advance->setUser($User);
            $manager->persist($advance);
            $manager->flush();
        }
        return $this;
    }


}
