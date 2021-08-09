<?php


namespace Swift\Framework\AnnotationLoader;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Exception;
use FilesystemIterator;
use Psr\Log\LoggerInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Reflector;
use SplFileInfo;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\Options;

class ClassAnnotationReader
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $scannedDirs = [];

    /**
     * @var array
     */
    private $annotations = [];

    /**
     * @var Cache
     */
    private $cache;

    private $options = [
        'cache' => [
            'enable' => true,
            'provider' => 'filesystem',
            'filesystem' => [
                'cacheDir' => ''
            ]
        ]
    ];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(array $options = [])
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        $this->options = Options::merge($this->options, $options);
    }
    /**
     * @param string $className
     * @return array
     * @throws
     */
    public function getAnnotations(string $className): array
    {
        $reflection = new \ReflectionClass($className);

        $annotations = [];
        $reader = $this->getReader();
        // class annotations

        $classAnnotations = $reader->getClassAnnotations($reflection) ?: [];
//        $this->logger->info(json_encode($reflection));

        foreach ($classAnnotations as $annotation) {
            $annotations[] = [
                'type' => 'class',
                'class' => $className,
                'annotation' => $annotation
            ];
        }
//        $this->logger->info(json_encode($annotations));
        // method annotations
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();
            $methodAnnotations = $reader->getMethodAnnotations($reflectionMethod) ?: [];
            foreach ($methodAnnotations as $annotation) {
                $annotations[] = [
                    'type' => 'method',
                    'class' => $className,
                    'name' => $methodName,
                    'annotation' => $annotation
                ];
            }
        }

        // property annotation
        foreach ($reflection->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty) ?: [];
            foreach ($propertyAnnotations as $annotation) {
                $annotations[] = [
                    'type' => 'property',
                    'class' => $className,
                    'name' => $propertyName,
                    'annotation' => $annotation
                ];
            }
        }

        return $annotations;
    }

    /**
     * @param array $dirs
     * @param string $annotationClass
     * @return array
     * @throws Exception
     */
    public function load(array $dirs, string $annotationClass): ?array
    {
        $annotations = [];
        $key = 'annotation::dirs_' . md5(serialize($dirs));
//        $this->logger->info($annotationClass);
        if (isset($this->annotations[$key])) {
            return $this->annotations[$key][$annotationClass] ?? [];
        }

//        if ($this->options['cache']['enable'] && $cache = $this->getCache()) {
//            $result = $cache->fetch($key);
//
//            if ($result !== false) {
//                foreach ($result as $k => &$group) {
//                    foreach ($group as &$item) {
//                        $item['reflection'] = $this->getReflector($item);
//                    }
//                }
//
//                $this->annotations[$key] = $result;
//                return $result[$annotationClass] ?? [];
//            }
//        }

        $classes = [];

        foreach ($dirs as $dir) {
            $this->logger->info('$dir', [
                'data'  =>  $dir,
            ]);
            $classes = array_merge($classes, $this->scanDir($dir));
        }

        $cached = [];

        foreach ($classes as $class) {

            foreach ($this->getAnnotations($class) as &$item) {
//                $this->logger->debug(json_encode($item));
                $clone = array_merge([], $item);
                $item['reflection'] = $this->getReflector($item);

//                $this->logger->info(json_encode($item['annotation']));
                $itemAnnotationClass = get_class($item['annotation']);
                $this->logger->info('$itemAnnotationClass', [
                    'data'  =>  $itemAnnotationClass,
                    'data1' =>  $annotationClass
                ]);
//                $this->logger->debug('itemAnnotationClass', [
//                    'data1'  =>  json_encode($itemAnnotationClass),
//                    'data2'  =>  json_encode($annotationClass)
//                ]);
                if ($itemAnnotationClass === $annotationClass) {
                    $annotations[] = $item;
                }
                $this->annotations[$key][$itemAnnotationClass][] = $item;
                $cached[$itemAnnotationClass][] = $clone;
            }
        }
//        if ($cache) {
//            $cache->save($key, $cached);
//        }
        $this->logger->debug(json_encode($this->annotations));
        return $annotations;
    }

    /**
     * @param array $item
     * @return Reflector
     * @throws ReflectionException
     */
    private function getReflector(array $item): Reflector
    {
        $reflection = new ReflectionClass($item['class']);
        switch ($item['type']) {
            case 'property':
                $reflection = $reflection->getProperty($item['name']);
                break;
            case 'method':
                $reflection = $reflection->getMethod($item['name']);
                break;
        }

        return $reflection;
    }

    /**
     * @param string $dir
     * @return string[]
     * @throws
     */
    public function scanDir(string $dir): ?array
    {
        if (isset($this->scannedDirs[$dir])) {
            return $this->scannedDirs[$dir];
        }
        $classes = [];
//        if ($cache = $this->getCache()) {
//            $classes = $cache->fetch($dir);
//        }
        if (empty($classes)) {
            $files = iterator_to_array(new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator(
                        $dir,
                        FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                    ),
                    function (SplFileInfo $current) {
                        return '.' !== substr($current->getBasename(), 0, 1);
                    }
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            ));
            usort($files, function (SplFileInfo $a, SplFileInfo $b) {
                return (string)$a > (string)$b ? 1 : -1;
            });

            foreach ($files as $file) {
                if (!$file->isFile() || '.php' !== substr($file->getFilename(), -4)) {
                    continue;
                }
                $this->logger->info(ClassFinder::find($file));
                $this->logger->info($file);
                if ($class = ClassFinder::find($file)) {
                    $classes[] = $class;
                }
            }

            $this->scannedDirs[$dir] = $classes;
//            if ($cache) {
//                $cache->save($dir, $classes);
//            }
        }

        return $classes;
    }

    /**
     * @return Reader
     * @throws \Exception
     */
    public function getReader(): Reader
    {
        try {
            if (!$this->reader) {
                $parser = new DocParser();
                $reader = new AnnotationReader($parser);
                if ($this->options['cache']['enable']) {
                    $this->cache = $this->getCache();
                    $this->reader = new CachedReader($reader, $this->cache);
                } else {
                    $this->reader = $reader;
                }
            }
        }catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        return $this->reader;
    }

    /**
     * @return Cache
     * @throws Exception
     */
    public function getCache(): Cache
    {
        if (! $this->cache) {
            $provider = $this->options['cache']['provider'];
            switch ($provider) {
                case 'filesystem':
                    $cacheDir = $this->options['cache']['filesystem']['cacheDir'];
                    $this->cache = new FilesystemCache($cacheDir);
                    break;
                default:
                    throw new Exception('Unsupported cache provider: ' . $provider);
            }
        }

        return $this->cache;
    }
}
