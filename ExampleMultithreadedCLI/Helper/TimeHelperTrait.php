<?php


namespace App\Helper;


trait TimeHelperTrait
{
    private $arrayCountDown = [];
    protected function startCountDown($key = 'default')
    {
        $this->arrayCountDown[$key] = microtime(true);

        return $this->arrayCountDown[$key];
    }

    protected function getReportTime($key = 'default')
    {
        $second=$this->getSeconds($key);
        return (floor($second) < $second) ? gmdate('H:i:s', $second).preg_replace('/.*(\.)/', '$1', $second) : gmdate('H:i:s', $second);
    }
    protected function getSeconds($key = 'default')
    {
        if (isset($this->arrayCountDown[$key])) {
            $second = (microtime(true) - $this->arrayCountDown[$key]);
        } else {
            $second = 0;
        }
        return $second;
    }
}