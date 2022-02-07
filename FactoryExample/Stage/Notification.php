<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraints as Assert;

class Notification extends AbstractStage
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    protected $NotificationTimestamp;

    /**
     * @return mixed
     */
    public function getNotificationTimestamp()
    {
        return $this->NotificationTimestamp;
    }

    /**
     * @param mixed $NotificationTimestamp
     */
    public function setNotificationTimestamp($NotificationTimestamp): void
    {
        $this->NotificationTimestamp = $NotificationTimestamp;
    }

    protected function getDefaultFields()
    {
        return ['email'];
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }


    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return self
     */
    public function saveDoctrine(Registry $doctrine, Advance $advance=null):self
    {
        $user=$advance->getUser();
        if($user instanceof User) {
            $user->setEmail($this->getEmail());
            $doctrine->getManager()->persist($user);
            $doctrine->getManager()->flush();
        }
        return $this;
    }
}
