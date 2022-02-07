<?php

namespace App\Services;

use App\Entity\Advance;
use App\Entity\BranchOffice;
use App\Entity\User;
use App\Entity\UserByPhone;
use App\Entity\Service as ServiceEntity;
use App\Helper\Stage\Exception\WrongStageException;
use App\Helper\Stage\Stage\AbstractStage;
use App\Helper\Stage\Stage\Finish;
use App\Helper\Stage\Stage\InitialUserData;
use App\Helper\Stage\Stage\Notification;
use App\Helper\Stage\Stage\Registration;
use App\Helper\Stage\Stage\Server;
use App\Helper\Stage\Stage\Service;

use App\Helper\Stage\Exception\WrongArgumentException;
use App\Repository\BranchOfficeRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Serializable;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Throwable;


class StageManager implements Serializable
{
    const SESSION_USER_ID='StageManager_USER_ID';
    const SESSION_TARGET_BRANCH_OFFICE='StageManager_TARGET_BRANCH_OFFICE';
    const SESSION_ADVANCE_ID='StageManager_ADVANCE_ID';
    const SESSION_STAGE_OBJECT='StageManager_STAGE_OBJECT';
    const SESSION_URL_LOGOUT_ESIA='StageManager_BACK_URL_LOGOUT';
    // Список стадий. Так же определяет их порядок
    private $stageClassMap = [
        'initial_user_data' => InitialUserData::class,
        'server' => Server::class,
        'service' => Service::class,
//        'duration' => Duration::class,
        'registration' => Registration::class,
//        'confirm' => Confirm::class,
        'notification' => Notification::class,
        'finish' => Finish::class,
    ];
    private $data;
    private $defaultData = [];
    private $currentStage = null;
    protected $requestStack;
    protected $currentRequest;
    /**
     * @var Registry
     */
    protected $doctrine;
    protected $entityManager;
    protected $security;
    protected $advance;
    /**
     * @var User|null
     */
    private $user;
    /**
     * @var BranchOffice|null
     */
    private $targetBranchOffice;

    public function __construct(RequestStack $requestStack, Registry $doctrine, Security $security)
    {
        $this->data=[];
        $this->security = $security;
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->requestStack = $requestStack;
        $this->currentRequest = $requestStack->getCurrentRequest();
        $this->advance=$this->getAdvanceEntityFromDb();
        $this->setDefaultData([]);
        $this->data = array_fill_keys(array_keys($this->stageClassMap), null);
        if (!is_null($this->currentRequest) ) {
            $stageManager = $this->currentRequest->getSession()->get(self::SESSION_STAGE_OBJECT, $this);
            $this->data = $stageManager->data;
            $stageData = json_decode($this->currentRequest->cookies->get(self::SESSION_STAGE_OBJECT, '[]'), true);
            $stageManager->setDefaultData(is_array($stageData) ? $stageData : []);
            $this->setCurrentStage($this->getCurrentStageFromRequest());
        }
        else {
            $stageManager = $this;
            $stageManager->setDefaultData([]);
        }
    }


    /**
     * @return mixed|string
     */

    private function getCurrentStageFromRequest(): ?string
    {
        $explodeString = explode('_', $this->currentRequest->get('_route'), 2);
        if (isset($explodeString[1]) && in_array($explodeString[1], array_keys($this->stageClassMap))) {
            return $explodeString[1];
        }
        return $this->getLastActualStage();
    }

    /**
     * @return ServiceEntity|null
     */
    public function getService():?ServiceEntity
    {
        $ServiceStage=$this->getStageData('service');
       if( $ServiceStage instanceof Service){
           return $ServiceStage->getService();
       }else{
           return null;
       }

    }

    /**
     * @return $this
     */
    public function save():self
    {
            $advance=$this->getAdvance();
            $listCompletedStage= $this->getCompletedStageList();
        if($this->isStagePassed('registration')&&empty($advance->getId())) {
            $this->initAdvanceData();

        }
            foreach($listCompletedStage as $keyStage=> $Stage)
            {
                //       if($Stage instanceof Registration){
//                    $Stage->saveDoctrine($this->doctrine.$advance);
//                }else

                if($Stage instanceof InitialUserData)
                {
                    $Stage->saveDoctrine($this->doctrine, $advance, ($this->currentStage!=="initial_user_data"));
                }else
                if($Stage instanceof AbstractStage) {
                    $Stage->saveDoctrine($this->doctrine, $advance);
                }
            }


            $this->entityManager->flush();

        $this->currentRequest->getSession()->set(self::SESSION_STAGE_OBJECT, $this);
        return $this;
    }
public function setAdvanceIdSession($AdvanceId)
{ $this->currentRequest->getSession()->set(self::SESSION_ADVANCE_ID,$AdvanceId);

}
    /**
     * @return Advance|null
     */
    public function getAdvance():?Advance
    {
        return $this->advance;
    }

    /**
     * @return Advance
     */
    private function initAdvanceData():Advance
    {
        $advance = $this->getAdvance();
        $user = $this->security->getUser();
        if($user instanceof \App\Helper\User){
            $advance->setUsername($user->getUsername());
        }
        $this->entityManager->persist( $advance);
        $this->entityManager->flush();
        $this->setAdvanceIdSession($advance->getId());
        return $advance;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        if(!($this->user instanceof User)) {
           $this->user=$this->getUserFromSession();
        }
        if(!($this->user instanceof User)&&$this->isStagePassed('initial_user_data')) {
            /**
             *@var  $stageUser InitialUserData
             */
           $stageUser= $this->getStageData('initial_user_data');

            $User=$stageUser->getUser();
            if($User instanceof User){
                $this->user=$User;
            }
        }
            return $this->user;
    }

    /**
     * @return BranchOffice|null
     */
    public function getBranchOffice():?BranchOffice
    {
      if($this->isStagePassed('server')){
          /**
           * @var $serverStage Server
           */
          $serverStage=$this->getStageData('server');
          /**
           * @var  $BranchOfficeRepository BranchOfficeRepository
           */
          $BranchOfficeRepository=$this->doctrine->getRepository(BranchOffice::class);

          $BranchOffice=$BranchOfficeRepository->findByIdOrKey($serverStage->getServerId());

        if($BranchOffice instanceof BranchOffice){
          return $BranchOffice;
        }
      }
      return null;
    }
    /**
     * @return string|null
     */
    public function getBranchOfficeName():?string
    {
        if($this->isStagePassed('server')) {
            /**
             * @var $serverStage Server
             */
            $serverStage = $this->getStageData('server');
            return $serverStage->getServerName();
        }
        return null;
    }

    /**
     * @param User|null $user
     * @return $this
     * @throws Exception
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        if(empty($user->getId()))
        { throw new Exception('StageManager User id Empty');
        }
        $this->currentRequest->getSession()
            ->set(self::SESSION_USER_ID,$user->getId());
        return $this;

    }
    /**
     * @param User|null $user
     * @return $this
     */
    public function setUrlLogoutESIA(string $backUrl): self
    {
        $this->currentRequest->getSession()
            ->set(self::SESSION_URL_LOGOUT_ESIA,$backUrl);
        return $this;

    }
    public function getUrlLogoutESIA():string
    {
        return $this->currentRequest->getSession()->get(self::SESSION_URL_LOGOUT_ESIA,'');

    }



    /**
     * @return User|null
     */
private function getUserFromSession():?User
{
    $id=$this->currentRequest->getSession()->get(self::SESSION_USER_ID);
    if(!empty($id)) {
            /**
             * @var UserRepository
             */
           $UserRepository= $this->doctrine->getRepository(User::class);
            $User=$UserRepository->find($id);
            if( $User instanceof  User){
                return $User;
            }

        }
    return null;

}

    /**
     * @return BranchOffice|null
     */
    public function getTargetBranchOffice(): ?BranchOffice
    {   if(!$this->targetBranchOffice)
            {
                $this->targetBranchOffice=$this->getTargetBranchOfficeFromSession();

            }
        return $this->targetBranchOffice;
    }

    /**
     * @param BranchOffice|null $targetBranchOffice
     * @return $this
     */
    public function setTargetBranchOffice(?BranchOffice $targetBranchOffice): self
    {
        if($targetBranchOffice instanceof BranchOffice) {
            $this->targetBranchOffice = $targetBranchOffice;
            $this->currentRequest->getSession()->set(self::SESSION_TARGET_BRANCH_OFFICE, $targetBranchOffice->getId());
        }
        return $this;
    }
    public function getTargetBranchOfficeFromSession():?BranchOffice
    {
        $id=$this->currentRequest->getSession()->get(self::SESSION_TARGET_BRANCH_OFFICE);
        if(!empty($id)) {
            /**
             * @var BranchOfficeRepository
             */
            $BranchOfficeRepository= $this->doctrine->getRepository(BranchOffice::class);
            $BranchOffice=$BranchOfficeRepository->find($id);
            if( $BranchOffice instanceof  BranchOffice){
                return $BranchOffice;
            }
        }
        return null;
    }



    /**
     * @return array
     */
    public function getDefaultData(): array
    {
        return $this->defaultData;
    }
    private function getAdvanceEntityFromDb():Advance
    {
        $advance=null;
        $advance_id = !is_null($this->currentRequest) ? $this->currentRequest->getSession()->get(self::SESSION_ADVANCE_ID) : null;
        if ($advance_id) {
            $advance = $this->entityManager->find(Advance::class, $advance_id);
        } else {
            $advance= new Advance();

        }
        return $advance;
    }


    public function reset()
    {   reset($this->stageClassMap);
        $this->setCurrentStage(key($this->stageClassMap));
        $this->setDefaultData([]);
        $this->data = array_fill_keys(array_keys($this->stageClassMap), null);
        $this->currentRequest->getSession()->remove(self::SESSION_STAGE_OBJECT);
        $this->currentRequest->getSession()->remove(self::SESSION_ADVANCE_ID);
        $this->currentRequest->getSession()->remove(self::SESSION_USER_ID);
        $this->currentRequest->getSession()->remove(self::SESSION_URL_LOGOUT_ESIA);
        $this->advance=null;

    }


    public function setDefaultData(array $defaultData): StageManager
    {
        $this->defaultData = $defaultData;
        return $this;
    }

    public function getDefaultDataArray(): array
    {
        $result = [];
        foreach ($this->data as $stageName => $stage) {
            if ($stage instanceof AbstractStage) {
                $result[$stageName] = $stage->getDefaultDataArray();
            } elseif (isset($this->defaultData[$stageName])) {
                $result[$stageName] = $this->defaultData[$stageName];
            }
        }

        return $result;
    }

    public function serialize(): ?string
    {
        ServiceEntity::setGroupFieldSerialize(ServiceEntity::GROUP_SERIALIZE_STAGE);
        ServiceEntity::setMaxLevelDeepSerialize(1);
        UserByPhone::setGroupFieldSerialize(User::GROUP_SERIALIZE_STAGE);
        UserByPhone::setMaxLevelDeepSerialize(1);

        return serialize($this->data);
    }


    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data=unserialize($serialized);
        if(is_array($data)){
            $this->data = $data;
        }
    }

    private function verify($stage)
    {
        if (!isset($this->stageClassMap[$stage])) {
            throw new WrongStageException('Нет такой стадии: ' . $stage);
        }
    }

    /**
     * @param Throwable $e
     * @return $this
     */
    public function setError(Throwable $e):self
    {
        $advance=$this->getAdvance();
        if($advance instanceof Advance) {
            $advance->setIsError(1);
            $advance->setErrorMsg(substr($e->getMessage(),0,255));
        $this->entityManager->persist($advance);
        $this->entityManager->flush();
        }

        return $this;

    }
    public function setCurrentStage($stage): StageManager
    {
        $this->verify($stage);
        $this->currentStage = $stage;

        return $this;
    }

    public function getCurrentStage()
    {
        return $this->currentStage;
    }

    /**
     * Получаем последнюю незаполненную стадию.
     *
     * @return string|null
     */
    public function getLastActualStage():?string
    {
        foreach ($this->data as $stage => $data) {
            if (is_null($data)) {
                return $stage;
            }
        }
        return null;
    }

    /**
     * @param string $keyStage
     * @return bool
     */
    public function isStagePassed(string $keyStage):bool
    {
        if(isset($this->data[$keyStage])){
        return ($this->data[$keyStage] instanceof $this->stageClassMap[$keyStage]);
        }
    return false;
    }

    private function checkStage($stage)
    {
        if (is_null($stage)) {
            $stage = $this->getCurrentStage();
        } else {
            $this->verify($stage);
        }

        return $stage;
    }

    /**
     * @param AbstractStage $data
     * @param null $stage
     * @return $this
     */
    public function setStageData(AbstractStage $data, $stage = null):self
    {
        $stage = $this->checkStage($stage);

        if (!$data instanceof $this->stageClassMap[$stage]) {
            throw new WrongArgumentException('Данные не соответствуют стадии: ' . $stage);
        }
        $this->data[$stage] = $data;

        return $this;
    }

    /**
     * @param null $stage
     * @return AbstractStage
     */
    public function getStageData($stage = null):AbstractStage
    {
        $stage = $this->checkStage($stage);

        if (isset($this->data[$stage])) {
            $result = clone $this->data[$stage];
        } else {
            $className = $this->stageClassMap[$stage];
            if(InitialUserData::class==$className){
                $result = new $className(new UserByPhone());
            }else {
                $result = new $className($this->defaultData[$stage] ?? []);
            }
        }
        return $result;
    }

    public function unsetStageData($stage = null)
    {
        $stage = $this->checkStage($stage);
        $this->data[$stage] = null;
    }

    /**
     * @param AbstractStage|null $stage
     * @return AbstractStage|null
     */
    public function getNextStage(AbstractStage $stage = null):?AbstractStage
    {
        $stage = $this->checkStage($stage);
        $order = array_keys($this->stageClassMap);

        $orderKey = array_search($stage, $order);

        if (isset($order[$orderKey + 1])) {
            $stage = $order[$orderKey + 1];
            if ($this->isStageValid($stage)) {
                return $stage;
            }
        }
        return null;
    }

    /**
     *
     * Узнаём предыдущую валидную стадию.
     * @param AbstractStage|null $stage
     * @return string|null
     */
    public function getPreviousStage(AbstractStage $stage = null):?string
    {
        $stage = $this->checkStage($stage);
        $order = array_keys($this->stageClassMap);
        $orderKey = array_search($stage, $order);
        if (isset($order[$orderKey - 1])) {
            $stage = $order[$orderKey - 1];
            if ($this->isStageValid($stage)) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Узнаём является ли стадия валидной.
     * @param null $stage
     * @return bool
     */
    public function isStageValid($stage = null):bool
    {
        $stage = $this->checkStage($stage);
        $lastActualStage = $this->getLastActualStage();

        if ($stage == $lastActualStage) {
            // текущая стадия всегда валидна
            return true;
        } elseif (in_array($lastActualStage, ['notification', 'finish'])) {
            // с этих стадий нельзя никуда перейти
            return false;
        } elseif($stage=='initial_user_data'){
            // возврат на стадию initial_user_data запрещён  т
            return false;
        } elseif (in_array($lastActualStage, ['registration', 'service'])
            && in_array($stage, $this->getPreviousStageList($lastActualStage))) {
            // с этих стадий можно перейти на любую назад
            return true;
        } else {
            // все остальные переходы запрещены
            return  false;
        }
    }

    /**
     * Получаем список предыдущих стадий.
     * @param string|null $stage
     * @return string[]
     */
    public function getPreviousStageList(string $stage = null):array
    {
        $stage = $this->checkStage($stage);
        $order = array_keys($this->stageClassMap);
        $orderKey = array_search($stage, $order);

        return array_slice($order, 0, $orderKey);
    }

    /**
     * @return array
     */
    public function getData():array
    {
        return $this->data;
    }

    /**
     * @return Request|null
     */
    public function getCurrentRequest(): ?Request
    {
        return $this->currentRequest;
    }

    /**
     * @return array
     */
    public function getCompletedStageList():array
    {
        $result=[];
        foreach ($this->data as $key=>$valueStage)
        {
            if(is_a($valueStage,$this->stageClassMap[$key]))
            {
                $result[$key]=$valueStage;
            }
        }
        return $result;
    }

}
