<?php

namespace App\Helper;

use Symfony\Component\Process\Process;

trait ProcessParallelTrait
{
    /**
     * @param $listProcess
     * @param int $limit
     * @param false $callStart
     * @param false $callProcess
     * @param bool $isSmoothStart использовать задержку запуска или нет
     * @param float $smoothRunningSeconds максимальнаая задержка для запуска следующей итерации (секунды)
     * @param float $ratioSmooth  поправка увеличения при старте
     *                              1 линейное увеличение <1 угасание темпов у величения >1 увеличение темпов увеличения
     */
    protected function runningProcessesInPortionsWithALimit($listProcess, $limit = 100,
                                                            $callStart = false,
                                                            $callProcess = false,
                                                            $callError= false,
                                                            $isSmoothStart=true,$smoothRunningSeconds = 1,$ratioSmooth=0)
    {
        $callProcess = is_callable($callProcess) ? $callProcess : function () {};
        $callStart = is_callable($callStart) ? $callStart : function ($count) {};
        $callError = is_callable($callError) ? $callError : function (Process $process) {};

        if($ratioSmooth===0) {
            // сходя из предположения что $smoothRunningSeconds примерно равен полному времени выполнения
            $ratioSmooth = $smoothRunningSeconds/$limit+1;
        }
        $countAllProcess = count($listProcess);
        $callStart($countAllProcess);
        $activeListProcess = array_slice($listProcess, (-1*$limit));
        $firstRunCount=0;
        /**
         * @var $Process Process
         */
        foreach ($activeListProcess as $Process) {
            $Process->start();
            $firstRunCount++;

            if($isSmoothStart){
                $ratio=($limit/$firstRunCount)*$ratioSmooth;
                $ratio = $ratio<1?$ratioSmooth:$ratio;
                $this->nanoSleep($smoothRunningSeconds/$ratio);
            }
        }
        $countEndedProcess = 0;

        while ($countEndedProcess < $countAllProcess) {
            foreach ($activeListProcess as $key =>$Process) {
                assert($Process instanceof Process);
                if($Process->isTerminated()){
                    //is not success exit code
                    if($Process->getExitCode()!=0){
                            $callError($Process);
                    }
                    unset($activeListProcess[$key]);
                    $callProcess();
                    $countEndedProcess++;
                }
            }
            $countActive=count($activeListProcess);
            $resolutionProcessCount=$limit-$countActive;
            if($resolutionProcessCount>0) {
                $newProcess = array_splice($listProcess, (-1 * $resolutionProcessCount));
                foreach ($newProcess as $Process) {
                    assert($Process instanceof Process);
                    if(!$Process->isRunning()) {
                        $Process->start();
                    }
                }
                $activeListProcess = array_merge($activeListProcess, $newProcess);
            }
          $this->nanoSleep(0.2);
        }


    }



    private function nanoSleep(float $secondFloat)
    {
        $secondInteger=floor($secondFloat);
        $nanoSecond=fmod($secondFloat, 1)*1000000000;
        time_nanosleep($secondInteger, $nanoSecond);
    }

}