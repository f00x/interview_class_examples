<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * Заглушка.
 */
class Finish extends AbstractStage
{
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
