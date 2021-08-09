<?php


namespace Swift\Framework\Config;


use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Psr\Log\LoggerInterface;
use Swift\Component\Singleton;
use Swift\Framework\Bootstrap\Bootstrap;
use Swift\Framework\Configurator\ConfiguratorInterface;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\File;
use Symfony\Component\Finder\Finder;

class ConfigManager
{
    use Singleton;
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $runtimeDir = '';

    public function __construct()
    {
    }

    public function setConfiguration(Configuration $config)
    {
        $this->config = $config;
        $this->rootDir = File::getRootDir();
        $this->runtimeDir = $this->rootDir . '/temp/runtime';
        $this->logger = LoggerFactory::getInstance()->getLogger();
//        $this->logger->info($this->rootDir);
        $this->sayGreetings();
        if ($config->getConf('application.debug') && is_dir($this->runtimeDir)) {
            $this->logger->info('Clearing runtime cache ...');
            File::removeDirRecursive($this->runtimeDir);
        }

        $classes = $this->scanComponents();
        $configurators = [];
        $properties = [];
        foreach ($classes as $class) {
            /* @var ConfiguratorInterface $configurator */
            $configurator = new $class;
            $configurators[] = $configurator;
            $properties = array_merge($properties, $configurator->getProperties());
        }

        $config->setProperties($properties);
        $this->logger->info(json_encode($config->getConf()));

        foreach ($configurators as $configurator) {
            if (method_exists($configurator, 'configure')) {
                call_user_func([$configurator, 'configure']);
            }
        }
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * @return Cache
     */
    public function getCache(): Cache
    {
        if (!$this->cache) {
            $this->cache = new FilesystemCache($this->getCacheDir());
        }
        return $this->cache;
    }

    private function getCacheDir(): string
    {
        if (!$this->cacheDir) {
            $this->cacheDir = $this->runtimeDir . '/configurator';
        }

        return $this->cacheDir;
    }

    public function scanComponents(): array
    {
        $cache = $this->getCache();
        $classes = $cache->fetch('configurator');
        if ($classes === false) {
            $classes = [];
        } else {
            $this->logger->debug('Components loaded from cache');
        }

        if (!$classes) {
            $files = (new Finder())
                    ->in($this->rootDir . '/vendor')
                    ->depth('<3')
                    ->name('composer.json');

            $files->append(
                (new Finder())
                ->in($this->rootDir)
                ->depth('0')
                ->name('composer.json'));
            $this->logger->debug(sprintf('%d metadata files found', count($files)));
            /* @var \SplFileInfo $file*/
            foreach ($files as $file) {
                $contents = file_get_contents($file->getPathname());
                $configure = json_decode($contents);
                if (isset($configure->extra->swift->configurators)) {
                    $classes = array_merge($classes, $configure->extra->swift->configurators);
                }
            }

            $cache->save('configurator', $classes);
        }
        $this->logger->debug(sprintf('%d components found', count($classes)));
        return $classes;
    }

    private function sayGreetings()
    {
        $this->logger->info('************************************************************');
        $this->logger->info('* Welcome to Swift - A powerful and easy to use framework   *');
        $this->logger->info('* It can run in either PHP-CLI, PHP-FPM or other mode      *');
        $this->logger->info('************************************************************');
    }
}
