<?php

namespace Swift\Framework\Bootstrap;

use Psr\Log\LoggerInterface;
use Swift\Component\Color;
use Swift\Component\Singleton;
use Swift\Framework\AnnotationLoader\AnnotationLoaderManager;
use Swift\Framework\Application\ApplicationInterface;
use Swift\Framework\Config\ConfigManager;
use Swift\Framework\Config\Configuration;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\File;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Bootstrap
{
    use Singleton;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private static $rootDir = '';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApplicationInterface
     */
    private $application;

    public function __construct(string $rootDir = '')
    {
        self::$rootDir = $rootDir;
        $this->eventDispatcher = new EventDispatcher();
        $this->initialize();
    }

    public function loadConfiguration()
    {
        if (!self::$rootDir) {
            self::$rootDir = self::getRootDir();
        }
        $file = sprintf("%s/config/config.php",self::$rootDir);
        if (!file_exists($file)) {
            $this->logger->error(Color::error("can not load config file {$file}") . "\n");
        }

        Configuration::getInstance()->loadFile($file);
        ConfigManager::getInstance()->setConfiguration(Configuration::getInstance());
    }

    public function initialize()
    {
        ini_set('error_reporting',E_ALL & ~E_NOTICE);
        date_default_timezone_set('UTC');
        $this->logger = LoggerFactory::getInstance()->getLogger();
        $this->logger->info('Booting init');
        $this->loadConfiguration();
    }

    public static function getRootDir() {
        if (!self::$rootDir) {
            self::$rootDir = File::getRootDir();
        }
        return self::$rootDir;
    }

    /**
     * 设置应用
     * @param ApplicationInterface $application
     */
    public function setApplication(?ApplicationInterface $application)
    {
        $this->application = $application;
        AnnotationLoaderManager::getInstance()->load();
        $this->logger->info('application ready ...');
    }

    public function getApplication():ApplicationInterface
    {
        return $this->application;
    }

    public function run()
    {
        return $this->application->run();
    }
}
