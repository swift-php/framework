<?php
namespace Swift\Framework\DependencyInjection;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use Swift\Component\Singleton;
use Swift\Framework\Exception\ServiceNotFoundException;
use Swift\Framework\Logger\LoggerFactory;
use Symfony\Component\DependencyInjection\Reference;

class Container implements ContainerInterface
{

    use Singleton;
    /**
     * @var ReflectionMethod[]
     */
    private $injectableMethods = [];

    /**
     * @var ReflectionProperty[]
     */
    private $injectableProperties = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Definition[]
     */
    private $definitions = [];

    /**
     * @var array
     */
    private $services = [];

    /**
     * Container constructor.
     * @throws
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
    }

    /**
     * @param string $id
     * @return mixed|object
     * @throws ReflectionException
     */
    public function get($id)
    {
        $this->logger->debug('Inject service: ' . $id);

//        if (isset($this->injectableMethods[$id])) {
//            return $this->loadInjectableMethod($id);
//        }

        $definition = $this->definitions[$id] ?? null;

        if (!$definition) {
            throw new ServiceNotFoundException($id);
        }
//
//        if ($service = $definition->getInstance()) {
//            return $service;
//        }

        try {
            $reflection = new ReflectionClass($definition->getClass());
        } catch (ReflectionException $exception) {
            throw new ServiceNotFoundException($id);
        }
        $this->logger->debug('Inject service111: ', [
            'data'  =>  $reflection->getConstructor(),
            'data1' =>  $reflection->getConstructor()->getParameters()
        ]);
        // 处理构造器参数依赖
        if ($constructor = $reflection->getConstructor()) {
            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();

                $this->logger->debug(
                    sprintf(
                        'Resolving constructor parameter: %s %s',
                        $id,
                        $name
                    )
                );

                $idList = [$name];

                if ($type = $parameter->getType()) {
                    $idList[] = $type->getName();
                }

                foreach ($idList as $item) {
                    if ($this->has($item)) {
                        $definition->addArgument(new Reference($item));
                        break;
                    }
                }
            }
        }

        // 注入构造器参数
        $arguments = [];
        foreach ($definition->getArguments() as $argument) {
            $arguments[] = $argument instanceof Reference
                ? $this->get($argument->getId())
                : $argument;
        }

        // 处理属性
        foreach ($definition->getProperties() as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setValue($value);
        }

        // 实例化
        $service = $arguments
            ? $reflection->newInstanceArgs($arguments)
            : $reflection->newInstance();

        // 回调方法
        foreach ($definition->getMethodCalls() as $method) {
            $params = [];

            foreach ($method[1] as $param) {
                $params[] = $param instanceof Reference
                    ? $this->get($param->getId())
                    : $param;
            }

            call_user_func_array([$service, $method[0]], $params);
        }

        // 回调configurator
        if ($configurator = $definition->getConfigurator()) {
            $configurator($service);
        }

        $this->services[$id] = $service;
        $definition->setInstance($service);

        return $service;
    }

    /**
     * @param string $id
     * @param object $service
     * @throws
     */
    public function set(string $id, object $service)
    {
        $this->configure($service);
        $this->services[$id] = $service;

        if (isset($this->definitions[$id])) {
            $this->definitions[$id]->setInstance($service);
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return
            isset($this->services[$id]) ||
            isset($this->definitions[$id]) ||
            isset($this->injectableMethods[$id]);
    }

    /**
     * @param string $id
     * @param string $class
     * @return Definition
     * @throws
     */
    public function register(string $id, string $class): Definition
    {
        $this->logger->debug(sprintf('Register service: %s', $class));

        // Get aspect proxy class

        $definition = new Definition($class);
        $definition->setConfigurator(
            function ($object) {
                $this->configure($object);
            }
        );
        $this->definitions[$class] = $definition;
        $this->definitions[$id] = $definition;

        return $definition;
    }

}
