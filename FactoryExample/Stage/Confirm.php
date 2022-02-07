<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraints as Assert;

class Confirm extends AbstractStage
{
    /**
     * @Assert\IsTrue(message="Проверочный код не совпадает")
     */
    protected $confirmed = false;

    protected $confirmNotificationTimestamp;

    public function getConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     *
     * @return $this
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConfirmNotificationTimestamp()
    {
        return $this->confirmNotificationTimestamp;
    }

    /**
     * @param mixed $confirmNotificationTimestamp
     */
    public function setConfirmNotificationTimestamp($confirmNotificationTimestamp): void
    {
        $this->confirmNotificationTimestamp = $confirmNotificationTimestamp;
    }

    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return self
     */
    public function saveDoctrine(Registry $doctrine, Advance $advance=null):self
    {
        // TODO: Implement saveDoctrine() method
        return $this;
    }

}
