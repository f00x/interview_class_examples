<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraints as Assert;

class Server extends AbstractStage
{
    protected $serverName;

    /** @Assert\NotBlank() */
    protected $serverId;

    protected function getDefaultFields()
    {
        return ['serverId'];
    }

    public function getServerName()
    {
        return $this->serverName;
    }

    public function getServerId()
    {
        return $this->serverId;
    }

    public function setServerName($serverName)
    {
        $this->serverName = $serverName;

        return $this;
    }

    public function setServerId($serverId)
    {
        $this->serverId = $serverId;

        return $this;
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
