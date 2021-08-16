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

        if (isset($this->injectableMethods[$id])) {
            return $this->loadInjectableMethod($id);
        }

        $definition = $this->definitions[$id] ?? null;

        if (!$definition) {
            throw new ServiceNotFoundException($id);
        }

        if ($service = $definition->getService()) {
            return $service;
        }

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
        if ($configurator = $reflection->getConstructor()) {
            foreach ($configurator->getParameters() as $parameter) {
                $name = $parameter->getName();
                $this->logger->debug(
                    sprintf(
                        'Resolving constructor parameter: %s %s',
                        $id,
                        $name
                    )
                );
                $idList = [$name];
                //返回参数的类型
                if ($type = $parameter->getType()) {
                    $idList[] = $type->getName();
                }
                foreach ($idList as $item) {
                    if ($this->has($item))  {
                        $definition->addArgument(new Reference($item));
                        break;
                    }
                }
            }
        }

        // 注入构造器参数
        $arguments = [];

        foreach ($definition->getArguments() as $argument) {
            $arguments[] = $argument instanceof Reference ? $this->get($argument) : $argument;
        }

        // 实例化
        $service = $arguments
            ? $reflection->newInstanceArgs($arguments)
            : $reflection->newInstance();

        // 回调configurator
        if ($configurator = $definition->getConfigurator()) {
            $configurator($service);
        }

        $this->services[$id] = $service;
        $definition->setService($service);

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
            $this->definitions[$id]->setService($service);
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

        $definition->setConfigurator(function ($object) {
            $this->configure($object);
        });

        $this->definitions[$class] = $definition;
        $this->definitions[$id] = $definition;

        return $definition;
    }

    /**
     * @param string $id
     * @param ReflectionMethod $method
     * @throws ReflectionException
     */
    public function addInjectableMethod(string $id, ReflectionMethod $method)
    {
        $this->logger->debug(
            sprintf(
                'Add injectable method: %s::%s => %s',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
                $id
            )
        );

        $this->injectableMethods[$id] = [
            'method'    =>  $method
        ];

        $returnType = $method->getReturnType()
            ? $method->getReturnType()->getName()
            : null;
        if ($returnType && (class_exists($returnType) || interface_exists($returnType))) {
            $reflector = new ReflectionClass($returnType);

            $this->injectableMethods[$returnType] = $this->injectableMethods[$id];
            $this->logger->info('addInjectableMethod', [
                'data'  =>  $this->injectableMethods
            ]);
            foreach ($reflector->getInterfaceNames() as $name) {
                $this->injectableMethods[$name] = $this->injectableMethods[$id];
            }
        }
    }

    public function addInjectableProperty(ReflectionProperty $property, callable $call,array $context)
    {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();

        $this->logger->debug(sprintf(
            'Add Injectable Property: %s::$%s => %s',
            $className,
            $propertyName,
            json_encode($context)
        ));

        $this->injectableProperties[$className][$propertyName] = [
            'property'  =>  $property,
            'loader'    =>  $call,
            'context'   =>  $context
        ];
    }

    public function loadInjectableProperty(string $className, string $propertyName)
    {
        $this->logger->debug(sprintf(
            'loading Injectable Property: %s::$%s',
            $className,
            $propertyName
        ));
        $definition = $this->injectableProperties[$className][$propertyName];
        array_unshift($definition['context'], $definition['property']);
        return call_user_func_array($definition['loader'],$definition['context']);
    }

    /**
     * @param string $id
     * @throws ReflectionException
     */
    public function loadInjectableMethod(string $id)
    {
        $this->logger->debug('Loading Injectable Method: ' . $id);
        /* @var ReflectionMethod $reflection*/
        $reflection = $this->injectableMethods[$id]['method'];
        $instance = $this->get($reflection->getDeclaringClass()->getName());

        $params = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType()->getName();
            $this->logger->debug('Loading reflection parameter: ' . $name);
            $this->logger->debug('Loading reflection parameter type: ' . $type);
        }
        $result = call_user_func_array([$instance,$reflection->getName()],$params);

        $this->set($id, $result);
        return $result;
    }

    /**
     * @param $object
     * @throws ReflectionException
     */
    private function configure($object)
    {
        $reflection = new ReflectionObject($object);
        $className = $reflection->getName();
        if (isset($this->injectableProperties[$className])) {
            foreach ($this->injectableProperties[$className] as $k => $v) {
                $property = $reflection->getProperty($k);
                $property->setAccessible(ReflectionProperty::IS_PUBLIC);
                $property->setValue($object, $this->loadInjectableProperty($className, $k));
            }
        }
    }

}
