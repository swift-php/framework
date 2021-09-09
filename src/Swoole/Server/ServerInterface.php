<?php


namespace Swift\Framework\Swoole\Server;


interface ServerInterface
{
    public function run();
    public function close();
}
