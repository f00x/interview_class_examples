<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraints as Assert;

class Registration extends AbstractStage
{
    /**
     * @Assert\NotBlank()
     */
    private $advanceDateTime;
    private $registrationDateTime;
    protected $number;

    /** @var string */
    protected $confirmationCode;

    /** @var int */
    protected $windowId;

    protected function getDefaultFields()
    {
        return [];
    }

    public function getAdvanceDatetime()
    {
        return $this->advanceDateTime;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setAdvanceDateTime(DateTime $dateTime)
    {
        $this->advanceDateTime = $dateTime;

        return $this;
    }

    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }


    public function getConfirmationCode()
    {
        return $this->confirmationCode;
    }

    public function getRegistrationDateTime()
    {
        return $this->registrationDateTime;
    }

    public function getWindowId()
    {
        return $this->windowId;
    }

    public function setConfirmationCode($confirmationCode)
    {
        $this->confirmationCode = $confirmationCode;

        return $this;
    }

    public function setRegistrationDateTime($registrationDateTime)
    {
        $this->registrationDateTime = $registrationDateTime;

        return $this;
    }

    public function setWindowId($windowId)
    {
        $this->windowId = $windowId;

        return $this;
    }

    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return self
     */
    public function saveDoctrine(Registry $doctrine, Advance $advance=null):self
    {
        if($advance instanceof Advance){

            $advance->fillByRegistrationStage($this);

        }
        return $this;
    }
}
