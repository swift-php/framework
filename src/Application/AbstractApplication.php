<?php


namespace Swift\Framework\Application;


use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swift\Framework\Logger\LoggerFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 抽象应用
 * Class AbstractApplication
 * @package System
 */
abstract class AbstractApplication implements ApplicationInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();

        // 错误处理
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);

//        $this->eventDispatcher = new EventDispatcher();
//        $this->container = Bootstrap::getContainer();
//        $this->context = new Context();
//        $this->annotationLoaderManager = Bootstrap::getAnnotationLoaderManager();
    }

}
