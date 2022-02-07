<?php

namespace App\Command;

use App\Entity\BranchOffice;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use App\Services\SlotListProvider\DamaskSlotListProvider;
use App\Services\SlotListProvider\DamaskSlotListProviderCLI;
use App\Services\TimeHelperTrait;
use App\Services\TimeLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\Process;

class CacheUpdateByServiceCommand extends Command
{
//    /** @var BranchOfficeRepository */
//    private $BranchOfficeRepository;
    /** @var ServiceRepository */
    private $ServiceRepository;

    /** @var DamaskSlotListProvider */
    private $damaskSlotListProvider;
    /**
     * @var LockFactory
     */
    private $LockFactory;
    /**
     * @var TimeLogger
     */
    private $TimeLogger;
    use TimeHelperTrait;

    public function __construct(ServiceRepository         $ServiceRepository,
                                DamaskSlotListProviderCLI $damaskSlotListProvider,
                                LockFactory               $LockFactory,
                                TimeLogger                $TimeLogger
    )
    {
//        $this->BranchOfficeRepository = $BranchOfficeRepository;
        $this->ServiceRepository = $ServiceRepository;
        $this->damaskSlotListProvider = $damaskSlotListProvider;
        $this->LockFactory = $LockFactory;
        $this->TimeLogger = $TimeLogger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('cache:slot:update:by_service')
            ->setDescription('update cache slot damask by service id')
            ->setHelp('')
            ->addOption('service-id', '-s', InputOption::VALUE_REQUIRED, 'Id service from DB', 0)
            ->addOption('ttl-multiplier', '-t', InputOption::VALUE_OPTIONAL,
                "Expiry time multiplier, For a reserve of time to complete.So that updating the user's cache does not work. 1=100% not change, default 2",
                2)
            ->addOption('follow', '-f', InputOption::VALUE_OPTIONAL,
                "endless script execution",
                0)
            ->addOption('get-test', '-g', InputOption::VALUE_NONE,
                "Receive Test Mode "
                )

        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repeatTime = $input->getOption('follow');
        $this->startCountDown();
        $listSlot = [];
        $countSlot = 0;
        $idService = $input->getOption('service-id');
        $service = $this->ServiceRepository->find($idService);
        $isGetTest=$input->getOption('service-id');
        $keyLock = "CacheUpdateByService_$idService";

        //if key not locked, lock other process
        /// ttl not work in FlockStorage https://symfony.com/doc/current/components/lock.html#available-stores
        if ($isGetTest){
            while (true) {

                $this->startCountDown('iteration_update');

                try {
                    $this->updateSlotsAction($service, $output);

                } catch (\Throwable $exception) {
                    $output->writeln($exception->getMessage());
                    continue;
                }

                $output->writeln('');
                $output->writeln('End Update timeExecute-' . $this->getReportTime('iteration_update'));
                $iterationSecond = $this->getSeconds('iteration_update');

                $this->sleep($repeatTime, $iterationSecond, $output);
            }


        }else {
            $this->damaskSlotListProvider->setIsRenewCache(true);
            $newEmptyTTL = 2 * $this->damaskSlotListProvider->getCacheTTLEmptyListSlot();
            $newNotEmptyTTL = 2 * $this->damaskSlotListProvider->getCacheTTLNotEmptyListSlot();
            $this->damaskSlotListProvider->setCacheTTLEmptyListSlot($newEmptyTTL);
            $this->damaskSlotListProvider->setCacheTTLNotEmptyListSlot($newNotEmptyTTL);
            $lockUpdateSlot = $this->LockFactory->createLock($keyLock);
            $this->TimeLogger->start();
            $TimeLockRelease = 0;
            $TimeSecondGetSlot = 0;
            if ($lockUpdateSlot->acquire()) {
                try {
                    if ($repeatTime > 0) {

                        while (true) {

                            $this->startCountDown('iteration_update');

                            try {
                                $this->updateSlotsAction($service, $output);

                            } catch (\Throwable $exception) {
                                $output->writeln($exception->getMessage());
                                continue;
                            }

                            $output->writeln('');
                            $output->writeln('End Update timeExecute-' . $this->getReportTime('iteration_update'));
                            $iterationSecond = $this->getSeconds('iteration_update');

                            $this->sleep($repeatTime, $iterationSecond, $output);
                        }


                    } else {
                        $this->updateSlotsAction($service, $output);
                    }


                } catch (\Throwable $exception) {
                    //unlock key
                    $this->startCountDown('LockRelease');
                    $lockUpdateSlot->release();
                    $TimeLockRelease = $this->getSeconds('LockRelease');
                    $this->TimeLogger->finish(['LockRelease' => $TimeLockRelease, 'getSlotList' => $TimeSecondGetSlot]);
                    throw $exception;
                }

                //unlock key
                $this->startCountDown('LockRelease');
                $lockUpdateSlot->release();
                $TimeLockRelease = $this->getSeconds('LockRelease');
            } else {
                $output->writeln("Action blocked as a parallel process - $keyLock");
            }

            $this->TimeLogger->finish(['LockRelease' => $TimeLockRelease, 'getSlotList' => $TimeSecondGetSlot]);
        }
        return Command::SUCCESS;
    }

    private function updateSlotsAction(Service $service, OutputInterface $output)
    {
        $BranchOffice = $service->getBranchOffice();
        if ($BranchOffice instanceof BranchOffice) {
            $this->startCountDown('getSlotList');
            $listSlot[$service->getId()] = $this->damaskSlotListProvider->getSlotList(
                $BranchOffice->getServerConfig(),
                $service->getDamaskAliasId(),
                5
            );
            $TimeSecondGetSlot = $this->getSeconds('getSlotList');
            $countSlot = count($listSlot[$service->getId()]);
            $output->writeln("Count slot get - $countSlot");
            $output->writeln("Time Execute " . $this->getReportTime());
            $output->writeln("Memory usage  MByte-" . round((memory_get_peak_usage(true) / 1048576), 3));
        }
    }

    private function calcBlur($lastTime, &$blurStartTimeSecond)
    {
        $targetTime = $this->damaskSlotListProvider->getCacheTTLNotEmptyListSlot();
        $index = $targetTime / $lastTime;
        $blurStartTimeSecond = $blurStartTimeSecond * $index * 0.95;
        $blurStartTimeSecond = round($blurStartTimeSecond, 4);

    }

    private function sleep($baseSleepTime, $secondsHaveAlreadyPassed, OutputInterface $output)
    {


        $sleepTime = $baseSleepTime - $secondsHaveAlreadyPassed;
        if ($sleepTime > 1) {
            $output->writeln("Fell asleep for $sleepTime seconds");
            sleep($sleepTime);
        }
    }

    private function write(OutputInterface $output, $message, $tag)
    {
        $output->writeln("<$tag>" . date('Y-m-d H:i:s') . ' ' . $message . "</$tag>");
    }

    private function writeError(OutputInterface $output, $message)
    {
        $this->write($output, $message, 'error');
    }

    private function writeInfo(OutputInterface $output, $message)
    {
        $this->write($output, $message, 'info');
    }

}
