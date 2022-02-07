<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Duration extends AbstractStage
{
    /**
     * @Assert\NotBlank()
     */
    protected $duration;
    protected $baseDuration;

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    public function getBaseDuration()
    {
        return $this->baseDuration;
    }

    public function setBaseDuration($baseDuration)
    {
        $this->baseDuration = $baseDuration;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validateBaseDuration(ExecutionContextInterface $context, $payload)
    {
        if (null === $this->getBaseDuration()) {
            $context->buildViolation('Не задана базовая продолжительность')
                    ->atPath('duration')
                    ->addViolation();
        }

        if ($this->getDuration() < $this->getBaseDuration()) {
            $context->buildViolation('Новая продолжительность не может быть меньше базовой')
                    ->atPath('duration')
                    ->addViolation();
        }
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
