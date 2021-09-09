<?php


namespace Swift\Framework\Application;


use Swift\Framework\Console\Command\CommandLoader;
use Symfony\Component\Console\Application;
use Throwable;

/**
 * Class ConsoleApplication
 * @package Swift\Framework\Application
 */
class ConsoleApplication extends AbstractApplication
{
    /**
     * @var Application
     */
    protected $app = null;

    /**
     * @var CommandLoader|null
     */
    protected $commandLoader = null;


    public function __construct()
    {
        parent::__construct();

        $this->app = new Application();
        $this->commandLoader = new CommandLoader();
        $this->commandLoader->setContainer($this->container);
        $this->app->setCatchExceptions(false);
        $this->app->setDispatcher($this->eventDispatcher);
        $this->app->setCommandLoader($this->commandLoader);

//        $this->handleEvent();
    }

    /**
     * @inheritDoc
     */
    public function errorHandler(int $type,
                                 string $message,
                                 string $file = null,
                                 int $line = null,
                                 array $context = null)
    {

    }

    /**
     * @inheritDoc
     */
    public function exceptionHandler(Throwable $exception)
    {

    }

    /**
     * 获取命令加载器
     * @return CommandLoader
     */
    public function getCommandLoader(): CommandLoader
    {
        return $this->commandLoader;
    }

    /**
     * 运行程序
     * @return mixed|void
     * @throws
     */
    public function run()
    {
        $this->app->run();
    }
}
