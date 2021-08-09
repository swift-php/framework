<?php


namespace Swift\Framework\AnnotationLoader;


use Exception;
use Psr\Log\LoggerInterface;
use Reflector;
use Swift\Framework\Config\ConfigManager;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\File;

abstract class AbstractAnnotationLoader implements AnnotationLoaderInterface
{

    /**
     * @var string[]
     */
    protected $scanDirs;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var ClassAnnotationReader
     */
    protected $reader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param $reflection
     * @param $annotation
     */
    abstract protected function handle(Reflector $reflection, object $annotation);

    public function __construct(array $scanDirs = [])
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
        $this->scanDirs = $scanDirs;
        class_exists($this->class);
        $this->addDefaultDirs();
        $this->reader = AnnotationLoaderManager::getInstance()->getReader();

    }

    /**
     * @throws Exception
     */
    public function load()
    {
        $this->logger->debug('abstract annotation loader array', [
            'data'  =>  $this->reader->load($this->scanDirs, $this->class),
            'data1' =>  $this->scanDirs,
            'data2' =>  $this->class
        ]);
        foreach ($this->reader->load($this->scanDirs, $this->class) as $item) {
            $this->handle($item['reflection'], $item['annotation']);
        }
    }

    public function addScanDirs($dirs = [])
    {
        $this->scanDirs = array_unique(array_merge($this->scanDirs, $dirs));
    }

    private function addDefaultDirs()
    {
        $configuration = ConfigManager::getInstance()->getConfiguration();

        $dirs = [];

        $srcDir = File::resolve($configuration->getConf('application.srcDir'));
        if (is_dir($srcDir)) {
            $dirs[] = $srcDir;
        }

        $appDir = File::resolve($configuration->getConf('application.appDir'));
        if (is_dir($appDir)) {
            $dirs[] = $appDir;
        }

        $this->addScanDirs($dirs);
    }
}
