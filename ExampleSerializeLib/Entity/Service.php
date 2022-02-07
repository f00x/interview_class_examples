<?php

namespace App\Entity;

use App\Model\EntitySerialize;
use App\Repository\ServiceRepository;
use DamaskApi\Entity\SOAP\CSOAPAlias;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ServiceRepository::class)
 * @ORM\Table(name="service",indexes={
 *     @ORM\Index(name="damask_alias_idx",columns={"damask_alias_id"}),
 *     @ORM\Index(name="damask_idx",columns={"damask_id"})}
 * )
 */
class Service extends EntitySerialize
{
    const GROUP_SERIALIZE_SHORT = ['id', 'class', 'name', 'sortIndex','damaskId' ];
    const GROUP_SERIALIZE_PUBLIC = ['id', 'class', 'name'];
    const GROUP_SERIALIZE_STAGE = ['id', 'class', 'name', 'sortIndex','damaskId','damaskAliasId','helpText','isDelete'] ;
    const GROUP_SERIALIZE_TREE = ['id', 'class', 'name','helpText','sortIndex'];
    public static $groupFieldSerialize = self::GROUP_SERIALIZE_FULL;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;


    /**
     * @ORM\Column(type="datetime")
     */
    protected $updateDateTime;

    /**
     * @ORM\ManyToOne(targetEntity=BranchOffice::class, inversedBy="listService")
     * @ORM\JoinColumn(nullable=false,name="branch_office_id", referencedColumnName="id")
     */
    protected $BranchOffice;

    /**
     * @ORM\Column(type="bigint")
     * Operation_id damask
     */
    protected $damaskId;

    /**
     * @ORM\Column(type="bigint")
     */
    protected $damaskAliasId;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isProtected;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class)
     */
    protected $listRolesAllowedUse;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sortIndex;

    /**
     * @ORM\ManyToOne(targetEntity=ServiceGroup::class, inversedBy="listService", cascade={"persist"})
     */
    protected $ServiceGroup;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isDelete=false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $helpText;

    public function __construct()
    {
        $this->listRolesAllowedUse = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUpdateDateTime(): ?\DateTimeInterface
    {
        return $this->updateDateTime;
    }

    public function setUpdateDateTime(\DateTimeInterface $updateDateTime): self
    {
        $this->updateDateTime = $updateDateTime;

        return $this;
    }

    public function getBranchOffice(): ?BranchOffice
    {
        return $this->BranchOffice;
    }

    public function setBranchOffice(?BranchOffice $BranchOffice): self
    {
        $this->BranchOffice = $BranchOffice;

        return $this;
    }

    public function getDamaskId(): ?string
    {
        return $this->damaskId;
    }

    public function setDamaskId(string $damaskId): self
    {
        $this->damaskId = $damaskId;

        return $this;
    }

    public function getIsProtected(): ?bool
    {
        return $this->isProtected;
    }

    public function setIsProtected(bool $isProtected): self
    {
        $this->isProtected = $isProtected;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getListRolesAllowedUse(): Collection
    {
        return $this->listRolesAllowedUse;
    }

    public function addListRolesAllowedUse(Role $listRolesAllowedUse): self
    {
        if (!$this->listRolesAllowedUse->contains($listRolesAllowedUse)) {
            $this->listRolesAllowedUse[] = $listRolesAllowedUse;
        }

        return $this;
    }

    public function removeListRolesAllowedUse(Role $listRolesAllowedUse): self
    {
        if ($this->listRolesAllowedUse->contains($listRolesAllowedUse)) {
            $this->listRolesAllowedUse->removeElement($listRolesAllowedUse);
        }

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(?int $sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getServiceGroup(): ?ServiceGroup
    {
        return $this->ServiceGroup;
    }

    public function setServiceGroup(?ServiceGroup $ServiceGroup): self
    {
        $this->ServiceGroup = $ServiceGroup;

        return $this;
    }

    public function getIsDelete(): ?bool
    {
        return $this->isDelete;
    }

    public function setIsDelete(bool $isDelete): self
    {
        $this->isDelete = $isDelete;

        return $this;
    }
    public function fillDefaultUpdateInsert()
    {
        $this->isDelete=false;
        $this->setIsProtected(false);
        $this->updateDateTime=new \DateTime();
    }

    public function fillByCSOAPAlias(CSOAPAlias $serviceSource)
    {
        if(isset($serviceSource->names[0]->text)) {
        $this->setName($serviceSource->names[0]->text);
        $this->setDamaskId($serviceSource->operation_id);
        $this->setDamaskAliasId($serviceSource->alias_id);
        }
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $this->helpText = $helpText;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getDamaskAliasId()
    {
        return $this->damaskAliasId;
    }

    /**
     * @param mixed $damaskAliasId
     * @return Service
     */
    public function setDamaskAliasId($damaskAliasId)
    {
        $this->damaskAliasId = $damaskAliasId;

        return $this;
    }



}
