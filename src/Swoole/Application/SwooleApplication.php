<?php


namespace Swift\Framework\Swoole\Application;


use Monolog\Logger;
use Swift\Framework\Application\ConsoleApplication;
use Swift\Framework\Swoole\Command\StartCommand;

/**
 * Class SwooleApplication
 * @package Swift\Framework\Swoole\Application
 * @property Logger $logger
 */
class SwooleApplication extends ConsoleApplication
{

    public function __construct()
    {
        parent::__construct();

        $this->app->setAutoExit(false);

//        $this->logger->pushProcessor(new ContextProcessor());

        $this->getCommandLoader()->register('start', StartCommand::class);
    }

    /**
     * @return void
     */
    public function run()
    {
        parent::run();
    }

}
