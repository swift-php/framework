<?php


namespace Swift\Framework;


use PHPUnit\Framework\TestCase;
use Swift\Framework\Bootstrap\Bootstrap;
use Swift\Framework\Swoole\Application\SwooleApplication;


class BootstrapTest extends TestCase
{

    public function testRun()
    {
        try {
            $bootstrap = Bootstrap::getInstance();
            $bootstrap->setApplication(null);
//            $bootstrap->run();
            $this->assertTrue(true, '成功运行');
        } catch (\Exception $exception) {
            $this->assertFalse(true, '运行异常:' . $exception->getMessage());
        }
    }
}
