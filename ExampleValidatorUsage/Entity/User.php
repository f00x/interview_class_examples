<?php

namespace App\Entity;

use App\Model\EntitySerialize;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type_code", type="string")
 * @ORM\DiscriminatorMap({
 *     "User"=User::class,
 *     "UserByPhone" = UserByPhone::class,
 *     "UserByESIA" = UserByESIA::class
 *     }
 *     )
 * @ORM\Table(name="user",indexes={
 *     @ORM\Index(name="user_phone_idx",columns={"phone"}),
 *     @ORM\Index(name="user_deperson_idx",columns={"is_depersonalized"})
 * }
 *     )
 *
 */
class User extends EntitySerialize
{

    const VALIDATOR_GROUP_UPDATE = "UpdateUser";
    const VALIDATOR_GROUP_CREATE_BY_PHONE = "CreateByPhone";
    const GROUP_SERIALIZE_SHORT = ['id', 'class', 'phone', 'email', 'name', 'surname', 'patronymic'];
    const GROUP_SERIALIZE_PUBLIC = ['id', 'class', 'phone', 'email', 'name', 'surname', 'patronymic'];
    const GROUP_SERIALIZE_STAGE = [
        'id',
        'class',
        'phone',
        'email',
        'name',
        'surname',
        'patronymic',
        'createDateTime',
        'editDateTime',
        'lastActivityDateTime',
    ];
    const CONSTRAIN_REGEXP_NAME='/(*UTF8)^[а-яё\-]+$/i';
    const CONSTRAIN_REGEXP_PHONE = '/^\+?[0-9]{10,11}$/';
    protected static $groupFieldSerialize = self::GROUP_SERIALIZE_SHORT;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;


    /**
     * @ORM\Column(type="string", length=255 , nullable=true)
     * @Assert\Regex(pattern=User::CONSTRAIN_REGEXP_PHONE,groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE,User::VALIDATOR_GROUP_UPDATE})
     * @Assert\NotBlank(groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE,User::VALIDATOR_GROUP_UPDATE})
     *
     */
    protected $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email(groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE,User::VALIDATOR_GROUP_UPDATE})
     */
    protected $email;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createDateTime;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $editDateTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastActivityDateTime;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Regex(pattern=User::CONSTRAIN_REGEXP_NAME,groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE})
     * @Assert\NotBlank(groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE})
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\NotBlank(groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE})
     * @Assert\Regex(pattern=User::CONSTRAIN_REGEXP_NAME, groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE})
     */
    protected $surname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex(pattern=User::CONSTRAIN_REGEXP_NAME,groups={User::VALIDATOR_GROUP_CREATE_BY_PHONE})
     */
    protected $patronymic;

    /**
     * @ORM\OneToMany(targetEntity=Advance::class, mappedBy="User")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $listAdvance;

    /**
     * @ORM\OneToMany(targetEntity=UserMessage::class, mappedBy="User", orphanRemoval=true)
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $listMessage;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $isDepersonalized;

    /**
     * @ORM\OneToMany(targetEntity=FeedbackMessage::class, mappedBy="user")
     */
    private $feedbackMessageList;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->setIsDepersonalized(false);
        $this->setLastActivityDateTime(new \DateTime());
        $this->setCreateDateTime(new \DateTime());
        $this->setEditDateTime(new \DateTime());
        $this->feedbackMessageList = new ArrayCollection();
    }


    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->listAdvance = new ArrayCollection();
        $this->setCreateDateTime(new \DateTime());
        $this->setEditDateTime($this->getCreateDateTime());
        $this->listMessage = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        if(is_null($phone)){
            $this->phone = null;
            return $this;
        }
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = preg_replace('/^[78]{0,1}([0-9]{10})$/', '+7$1', $phone);
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCreateDateTime(): ?\DateTimeInterface
    {
        return $this->createDateTime;
    }

    public function setCreateDateTime(\DateTimeInterface $createDateTime): self
    {
        $this->createDateTime = $createDateTime;

        return $this;
    }

    public function getEditDateTime(): ?\DateTimeInterface
    {
        return $this->editDateTime;
    }

    public function setEditDateTime(\DateTimeInterface $editDateTime): self
    {
        $this->editDateTime = $editDateTime;

        return $this;
    }

    public function getLastActivityDateTime(): ?\DateTimeInterface
    {
        return $this->lastActivityDateTime;
    }

    public function setLastActivityDateTime(?\DateTimeInterface $lastActivityDateTime): self
    {
        $this->lastActivityDateTime = $lastActivityDateTime;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $this->transformationName($name);

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        if (!empty($surname)) {
            $this->surname = $this->transformationName($surname);
        } else {
            $this->surname = null;
        }

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): self
    {
        if (!empty($patronymic)) {
            $this->patronymic = $this->transformationName($patronymic);
        } else {
            $this->patronymic = null;
        }

        return $this;
    }

    /**
     * @return Collection|Advance[]
     */
    public function getListAdvance(): Collection
    {
        return $this->listAdvance;
    }

    public function addAdvance(Advance $advance): self
    {
        if (!$this->listAdvance->contains($advance)) {
            $this->listAdvance[] = $advance;
            $advance->setUser($this);
        }

        return $this;
    }

    public function removeAdvance(Advance $advance): self
    {
        if ($this->listAdvance->contains($advance)) {
            $this->listAdvance->removeElement($advance);
            // set the owning side to null (unless already changed)
            if ($advance->getUser() === $this) {
                $advance->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    public function transformationName(string $name): string
    {
        $replaceName = mb_ereg_replace('/[^А-яЁё\-]+/i', '', $name);
        $replaceName = mb_convert_case($replaceName, MB_CASE_TITLE);

        return $replaceName;
    }

    /**
     * @param $SourceArray
     * @return $this
     */
    public function fillByApiArray($SourceArray): User
    {
        if (isset($SourceArray['phone'])) {
            $this->setPhone($SourceArray['phone']);
        }
        if (isset($SourceArray['email'])) {
            $this->setEmail($SourceArray['email']);
        }
        if (isset($SourceArray['name'])) {
            $this->setName($SourceArray['name']);
        }
        if (isset($SourceArray['surname'])) {
            $this->setSurname($SourceArray['surname']);
        }
        if (isset($SourceArray['patronymic'])) {
            $this->setPatronymic($SourceArray['patronymic']);
        }
            $this->setIsDepersonalized(false);
        return $this;
    }

    public function getFullName()
    {
        return "{$this->getSurname()} {$this->getName()} {$this->getPatronymic()}";
    }

    /**
     * @return Collection|UserMessage[]
     */
    public function getListMessage(): Collection
    {
        return $this->listMessage;
    }

    public function addListMessage(UserMessage $listMessage): self
    {
        if (!$this->listMessage->contains($listMessage)) {
            $this->listMessage[] = $listMessage;
            $listMessage->setUser($this);
        }

        return $this;
    }

    public function removeListMessage(UserMessage $listMessage): self
    {
        if ($this->listMessage->contains($listMessage)) {
            $this->listMessage->removeElement($listMessage);
            // set the owning side to null (unless already changed)
            if ($listMessage->getUser() === $this) {
                $listMessage->setUser(null);
            }
        }

        return $this;
    }

    public function getIsDepersonalized(): ?bool
    {
        return $this->isDepersonalized;
    }

    public function setIsDepersonalized(?bool $isDepersonalized): self
    {
        $this->isDepersonalized = $isDepersonalized;

        return $this;
    }

    public function depersonalize()
    {
        $this->setIsDepersonalized(true);
        $this->setName('Защищено')
            ->setSurname(null)
            ->setPatronymic(null)
            ->setPhone(null)
            ->setEmail(null);
    }

    /**
     * @return Collection|FeedbackMessage[]
     */
    public function getFeedbackMessageList(): Collection
    {
        return $this->feedbackMessageList;
    }

    public function addFeedbackMessageList(FeedbackMessage $feedbackMessageList): self
    {
        if (!$this->feedbackMessageList->contains($feedbackMessageList)) {
            $this->feedbackMessageList[] = $feedbackMessageList;
            $feedbackMessageList->setUser($this);
        }

        return $this;
    }

    public function removeFeedbackMessageList(FeedbackMessage $feedbackMessageList): self
    {
        if ($this->feedbackMessageList->removeElement($feedbackMessageList)) {
            // set the owning side to null (unless already changed)
            if ($feedbackMessageList->getUser() === $this) {
                $feedbackMessageList->setUser(null);
            }
        }

        return $this;
    }


}
