<?php


namespace Swift\Framework\Event;


use Symfony\Contracts\EventDispatcher\Event;

class ProcessExitEvent extends Event
{

    public $isMaster = false;

    public $isWorker = false;

    public $pid = 0;

    public $signal = 0;
}
