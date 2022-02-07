<?php

namespace App\Helper\Stage\Stage;

use App\Entity\Advance;
use Doctrine\Bundle\DoctrineBundle\Registry;
use App\Entity\Service as ServiceEntity;

class Service extends AbstractStage
{
    /**
     * @var $Service ServiceEntity|null
     */
    protected $Service;

    /**
     * @return ServiceEntity|null
     */
    public function getService(): ?ServiceEntity
    {
        return $this->Service;
    }

    /**
     * @param ServiceEntity $Service
     */
    public function setService(ServiceEntity $Service): void
    {
        $this->Service = $Service;
    }

    /**
     * @return string|null
     */
    public function getServiceId():?string
    {
        if($this->getService() instanceof ServiceEntity){
            return $this->getService()->getDamaskAliasId();
        }
        return null;
    }


    /**
     * @param Registry $doctrine
     * @param Advance|null $advance
     * @return self
     */
    public function saveDoctrine(Registry $doctrine, Advance $advance=null):self
    {
        if($advance instanceof Advance&&$this->getService() instanceof ServiceEntity&&!empty($advance->getId()))
        {
            $service=$this->getService();
            $serviceRepository=$doctrine->getRepository(ServiceEntity::class);
            $serviceFromDb=$serviceRepository->find($service->getId());
            if($serviceFromDb instanceof  ServiceEntity)
            {$advance->setService($serviceFromDb);
                $doctrine->getManager()->persist($advance);
            }


        }
        return $this;
    }
}
