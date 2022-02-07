<?php

namespace App\Entity;

use App\Model\EntitySerialize;
use App\Repository\ServiceGroupRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ServiceGroupRepository::class)
 */
class ServiceGroup extends EntitySerialize
{

    const GROUP_SERIALIZE_SHORT = ['id', 'class', 'name', 'damaskId', 'serverId', 'host'];
    const GROUP_SERIALIZE_PUBLIC = [
        'id',
        'class',
        'name',
    ];
    const GROUP_SERIALIZE_PUBLIC_TREE = [
        'id',
        'class',
        'name',
        'tree',
    ];

    protected static $groupFieldSerialize = self::GROUP_SERIALIZE_SHORT;
    protected static $isAccessProtected = false;
    protected static $isAccessDeleted = false;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint")
     */
    private $damaskId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sortIndex;

    /**
     * @ORM\OneToMany(targetEntity=Service::class, mappedBy="ServiceGroup", cascade={"persist"})
     */
    private $listService;

    /**
     * @ORM\ManyToOne(targetEntity=ServiceGroup::class, inversedBy="listChildrenGroup")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=ServiceGroup::class, mappedBy="parent", cascade={"persist"})
     */
    private $listChildrenGroup;

    /**
     * @ORM\ManyToOne(targetEntity=BranchOffice::class, inversedBy="listServiceGroup")
     * @ORM\JoinColumn(nullable=false)
     */
    private $BranchOffice;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDelete = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isProtected = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updateDateTime;

    public function __construct()
    {
        $this->listService = new ArrayCollection();
        $this->listChildrenGroup = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    /**
     * @return Collection|Service[]
     */
    public function getListService(): Collection
    {
        $isDeletedVisible = self::isAccessDeleted();
        $isAccessProtected = self::isAccessProtected();
            $filterList = new ArrayCollection();
            /** @var  $service Service */
            foreach ($this->listService as $service) {
                if ($service->getIsDelete() && !$isDeletedVisible) {
                    continue;
                }
                if ($service->getIsProtected() && !$isAccessProtected) {
                    continue;
                }
                $filterList->add($service);
            }
            return $filterList;
    }

    public function addListService(Service $Service): self
    {
        if (!$this->listService->contains($Service)) {
            $this->listService[] = $Service;
            $Service->setServiceGroup($this);
        }

        return $this;
    }

    public function removeListService(Service $listService): self
    {
        if ($this->listService->contains($listService)) {
            $this->listService->removeElement($listService);
            // set the owning side to null (unless already changed)
            if ($listService->getServiceGroup() === $this) {
                $listService->setServiceGroup(null);
            }
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getListChildrenGroup(): Collection
    {
        $isDeletedVisible = self::isAccessDeleted();
        $isAccessProtected = self::isAccessProtected();

        $filterList = new ArrayCollection();
        /** @var  $serviceGroup ServiceGroup */
        foreach ($this->listChildrenGroup as $serviceGroup) {
            if ($serviceGroup->getIsDelete() && !$isDeletedVisible) {
                continue;
            }
            if ($serviceGroup->getIsProtected() && !$isAccessProtected) {
                continue;
            }
            $filterList->add($serviceGroup);

        }
        return $filterList;
    }

    public function addListChildrenGroup(self $listChildrenGroup): self
    {
        if (!$this->listChildrenGroup->contains($listChildrenGroup)) {
            $this->listChildrenGroup[] = $listChildrenGroup;
            $listChildrenGroup->setParent($this);
        }

        return $this;
    }

    public function removeListChildrenGroup(self $listChildrenGroup): self
    {
        if ($this->listChildrenGroup->contains($listChildrenGroup)) {
            $this->listChildrenGroup->removeElement($listChildrenGroup);
            // set the owning side to null (unless already changed)
            if ($listChildrenGroup->getParent() === $this) {
                $listChildrenGroup->setParent(null);
            }
        }

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

    public function fillSourceDamask(array $SourceDamask): self
    {

        $this->setName($SourceDamask['name']);
        $this->setDamaskId($SourceDamask['group_id']);

        return $this;
    }

    public function fillDefaultUpdateInsert()
    {
        $this->isDelete = false;
        $this->updateDateTime = new DateTime();
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

    public function getIsProtected(): ?bool
    {
        return $this->isProtected;
    }

    public function setIsProtected(bool $isProtected): self
    {
        $this->isProtected = $isProtected;

        return $this;
    }

    public function getUpdateDateTime(): ?DateTimeInterface
    {
        return $this->updateDateTime;
    }

    public function setUpdateDateTime(DateTimeInterface $updateDateTime): self
    {
        $this->updateDateTime = $updateDateTime;

        return $this;
    }

    /**
     * @return array|false[]
     * @throws \Exception
     */
    public function jsonSerialize(): array
    {
        $baseArray = $this->getArrayBase();

        if (in_array('tree', self::$groupFieldSerialize)) {
            $baseArray['tree'] = $this->getTree();
        }

        return $baseArray;
    }

    /**
     * @return array
     */
    public function getTree(): array
    {

        $listService = $this->getListService();

        $listGroup = $this->getListChildrenGroup();
        $compareList = [];
        foreach ($listService as $Service) {
            $compareList[$Service->getSortIndex()] = $Service;
        }
        foreach ($listGroup as $serviceGroup) {
            $compareList[$serviceGroup->getSortIndex()] = $serviceGroup;
        }
        uksort(
            $compareList,
            function ($aKey, $bKey): int {
                if ($aKey == $bKey) {
                    return 0;
                } elseif ($aKey < $bKey) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $compareList;


    }

    /**
     * @return bool
     */
    public static function isAccessProtected(): bool
    {
        return self::$isAccessProtected;
    }

    /**
     * @param bool $isAccessProtected
     */
    public static function setIsAccessProtected(bool $isAccessProtected): void
    {
        self::$isAccessProtected = $isAccessProtected;
    }

    /**
     * @return bool
     */
    public static function isAccessDeleted(): bool
    {
        return self::$isAccessDeleted;
    }

    /**
     * @param bool $isAccessDeleted
     */
    public static function setIsAccessDeleted(bool $isAccessDeleted): void
    {
        self::$isAccessDeleted = $isAccessDeleted;
    }



    public function setTreeNonDBSaveAddTree($listGroup, $listServices)
    {
        $this->listService = new ArrayCollection($listServices);
        $this->listChildrenGroup = new ArrayCollection($listGroup);
    }
}
