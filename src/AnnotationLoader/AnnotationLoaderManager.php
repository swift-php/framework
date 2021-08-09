<?php
namespace Swift\Framework\AnnotationLoader;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Psr\Log\LoggerInterface;
use Swift\Component\Singleton;
use Swift\Framework\Config\ConfigManager;
use Swift\Framework\Logger\LoggerFactory;

class AnnotationLoaderManager
{
    use Singleton;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AnnotationLoaderInterface[]
     */
    private $loaders = [];

    /**
     * @var ClassAnnotationReader
     */
    private static $reader = null;

    public function __construct()
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
        AnnotationRegistry::registerLoader('class_exists');
    }


    public function register(AnnotationLoaderInterface $loader)
    {

        $this->logger->info('Register loader: ' . get_class($loader));

        $this->loaders[] = $loader;
    }

    public function getReader(): ClassAnnotationReader
    {
        if (!self::$reader) {
            $configuration = ConfigManager::getInstance()->getConfiguration();
            self::$reader = new ClassAnnotationReader($configuration->getConf('annotation'));
        }
        return self::$reader;
    }


    public function load()
    {
        foreach ($this->loaders as $loader) {
            $this->logger->info('aaaabbbccc', [
                'data'  =>  $loader
            ]);
            $loader->load();
        }
    }
}
