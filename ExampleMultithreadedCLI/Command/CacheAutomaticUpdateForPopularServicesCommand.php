<?php

namespace App\Command;


use App\Helper\ProcessParallelTrait;
use App\Kernel;
use App\Repository\ServiceRepository;
use App\Services\SlotListProvider\DamaskSlotListProvider;
use App\Helper\TimeHelperTrait;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheAutomaticUpdateForPopularServicesCommand extends Command
{
    use TimeHelperTrait;
    use ProcessParallelTrait;

    protected static $defaultName = 'cache:auto:slot:update:popular_services';
    protected static $defaultDescription = 'Add a short description for your command';
    const CACHE_KEY_LIST_SERVICE_ID = 'CacheAutomaticUpdateForPopularServicesCommand_getListTopServiceId';
    const LOCK_PARALLEL_PROCESS_KEY = 'CacheAutomaticUpdateForPopularServicesCommand';


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
     * @var string
     */
    private $projectDir;
    /**
     * @var CacheInterface
     */
    protected $cacheService;
    /**
     * @var LockInterface
     */
    protected $lockUpdateSlot;

    public function __construct(string                 $projectDir,
                                ServiceRepository      $ServiceRepository,
                                DamaskSlotListProvider $damaskSlotListProvider,
                                LockFactory            $LockFactory,
                                CacheInterface         $cacheService
    )
    {
        $this->ServiceRepository = $ServiceRepository;
        $this->damaskSlotListProvider = $damaskSlotListProvider;
        $this->LockFactory = $LockFactory;
        $this->cacheService = $cacheService;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->addOption('limit-process', 'p',
                InputOption::VALUE_OPTIONAL, "limit parallel process", 250)
            ->addOption('limit-count', 'c',
                InputOption::VALUE_OPTIONAL, "Count top popular  services processing", 300)
            ->addOption('limit-day-rating', 'd',
                InputOption::VALUE_OPTIONAL, "How many previous days are included in the rating", 7)
            ->addOption('limit-minimum-rating', 'j',
                InputOption::VALUE_OPTIONAL, "Minimum entry rating. Recorded people for the period ", 10)
            ->addOption('blur', 'b',
                InputOption::VALUE_NONE, "blurring the startup time of child processes")
            ->addOption('blur-ratio', 'r',
                InputOption::VALUE_OPTIONAL, "Minimum entry rating. Recorded people for the period ", 0.93)
            ->addOption('blur-start', 's',
                InputOption::VALUE_OPTIONAL, "Minimum entry rating. Recorded people for the period ", 0.1)
            ->addOption('follow', '-f', InputOption::VALUE_NONE, "Child processes are not restarted");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limitProcess = $input->getOption('limit-process');
        $limitCount = $input->getOption('limit-count');
        $limitDayRating = $input->getOption('limit-day-rating');
        $limitMinimumCountRating = $input->getOption('limit-minimum-rating');
        $isBlur = $input->getOption('blur');
        $blurStartSecond = $input->getOption('blur-start');
        $blurRatio = $input->getOption('blur-ratio');
        $this->cacheService->delete(self::CACHE_KEY_LIST_SERVICE_ID);
        $isFollow = $input->getOption('follow');

        $this->lockUpdateSlot = $lockUpdateSlot = $this->LockFactory->createLock(self::LOCK_PARALLEL_PROCESS_KEY);
        if ($lockUpdateSlot->acquire()) {
            try {
                if ($isFollow) {
                    $listProcess = $this->getListProcess($limitDayRating, $limitMinimumCountRating, $limitCount,$isFollow);
                    $this->runningProcessesInPortionsWithALimit($listProcess, $limitProcess, false,
                        function () use ($output) {
//                       $output->write('|');
                        },
                        function (Process $process) use ($output) {
                      $out1=$process->getCommandLine();
                           $out2=$process->getErrorOutput();
                          $output->writeln('error '.$process->getCommandLine());
                            $output->writeln($process->getErrorOutput());
                        }, $isBlur, $blurStartSecond, $blurRatio);

                } else {
                    while (true) {
                        // $output->writeln('createList process');
                        //$this->startCountDown('create_list_process');

                        // $output->writeln('list created '.$this->getReportTime('create_list_process'));
                        $output->writeln("Start new Update blurStartTimeSecond=$blurStartSecond blurRatio=$blurRatio");
                        $this->startCountDown('iteration_update');

                        try {
                            $listProcess = $this->getListProcess($limitDayRating, $limitMinimumCountRating, $limitCount);
                            $this->runningProcessesInPortionsWithALimit($listProcess, $limitProcess, false,
                                function () use ($output) {
//                       $output->write('|');
                                },
                                function (Process $process) use ($output) {
                       $out1=$process->getCommandLine();
                           $out2=$process->getErrorOutput();
                           $output->writeln('error');
                                }, $isBlur, $blurStartSecond, $blurRatio);

                        } catch (\Throwable $exception) {

                            $output->write($exception->getMessage());
//                            throw $exception;
                        }
                        $output->writeln('');
                        $output->writeln('End Update timeExecute-' . $this->getReportTime('iteration_update'));
                        $iterationSecond = $this->getSeconds('iteration_update');
                        $this->calcBlur($iterationSecond, $blurStartSecond);

                        $this->sleep($iterationSecond, $output);

                    }
                }
            } catch (\Throwable $exception) {
                $lockUpdateSlot->release();
                throw $exception;
            }
            //unlock key
            $lockUpdateSlot->release();
        } else {
            $output->writeln("Action blocked as a parallel process -" . self::LOCK_PARALLEL_PROCESS_KEY);
        }


        return Command::SUCCESS;
    }

    private function getListProcess($limitDayRating, $limitMinimumCountRating, $limitCount,$isFollow)
    {
        $list = $this->getListTopServiceId($limitDayRating, $limitMinimumCountRating, $limitCount);
        $listProcess = [];
        if (is_iterable($list)) {
            foreach ($list as $data) {
                $listProcess[$data['id']] = $this->createProcess($data['id'],$isFollow);
            }
        }
        return $listProcess;
    }

    private function calcBlur($lastTime, &$blurStartTimeSecond)
    {
        $targetTime = $this->damaskSlotListProvider->getCacheTTLNotEmptyListSlot();
        $index = $targetTime / $lastTime;
        $blurStartTimeSecond = $blurStartTimeSecond * $index * 0.95;
        $blurStartTimeSecond = round($blurStartTimeSecond, 4);

    }

    private function sleep($secondsHaveAlreadyPassed, OutputInterface $output)
    {
        $baseSleepTime = $this->damaskSlotListProvider->getCacheTTLNotEmptyListSlot();

        $sleepTime = $baseSleepTime - $secondsHaveAlreadyPassed;
        if ($sleepTime > 1) {
            $output->writeln("Fell asleep for $sleepTime seconds");
            sleep($sleepTime);
        }
    }

    private function getListTopServiceId($countDay = 7, $minLimit = 10, $countLimit = 300)
    {
        $TTLCache = new \DateInterval('P1D');
        return $this->cacheService->get(self::CACHE_KEY_LIST_SERVICE_ID,
            function (ItemInterface $item) use ($countDay, $minLimit, $TTLCache, $countLimit) {
                $startDateTime = new \DateTime();
                $startDateTime->modify("- $countDay day midnight");
                $finishDateTime = new \DateTime();
                $finishDateTime->modify('midnight');
                $list = $this->ServiceRepository->getTopPopularServiceId($startDateTime, $finishDateTime, $minLimit);
                $item->expiresAfter($TTLCache);
                return array_slice($list, 0, $countLimit);
            }
        );


    }

    private function createProcess($serviceId, $isFollow = false): Process
    {
        $arguments = ["php",
            "bin/console",
            "cache:slot:update:by_service",
            "--service-id",
            $serviceId];
        if ($isFollow) {
            $arguments[] = "--follow";
            $arguments[] = $this->damaskSlotListProvider->getCacheTTLNotEmptyListSlot();
        }

        $process = new Process($arguments);
        $process->setWorkingDirectory($this->projectDir);
       // $process->disableOutput();

        return $process;
    }

    public function __destruct()
    {
        $this->lockUpdateSlot->release();
    }
}
