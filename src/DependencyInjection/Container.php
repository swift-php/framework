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

        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (isset($this->injectableMethods[$id])) {
            return $this->loadInjectableMethod($id);
        }

        $definition = $this->definitions[$id] ?? null;

        if (!$definition) {
            throw new ServiceNotFoundException($id);
        }

        if ($service = $definition->getInstance()) {
            return $service;
        }

        try {
            $reflection = new ReflectionClass($definition->getClass());
        } catch (ReflectionException $exception) {
            throw new ServiceNotFoundException($id);
        }

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
        $this->addDefinition($id, $definition, $class);

        return $definition;
    }

    /**
     * @param string $className
     * @param string $propertyName
     * @return mixed
     */
    private function loadInjectableProperty(string $className, string $propertyName)
    {
        $this->logger->debug(
            sprintf(
                'Loading injectable property: %s::$%s',
                $className,
                $propertyName
            )
        );

        $definition = $this->injectableProperties[$className][$propertyName];
        array_unshift($definition['context'], $definition['property']);

        return call_user_func_array(
            $definition['loader'],
            $definition['context']
        );
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


    /**
     * @param string $id
     * @param Definition $definition
     * @param string|null $sourceClass
     * @throws ReflectionException
     */
    public function addDefinition(
        string $id,
        Definition $definition,
        string $sourceClass = null
    ) {
        $class = $sourceClass ?? $definition->getClass();
        $this->definitions[$id] = $definition;
        $this->definitions[$class] = $definition;

        if (!class_exists($class)) {
            return;
        }

        foreach ((new ReflectionClass($class))->getInterfaceNames() as $name) {
            $this->definitions[$name] = $definition;
        }
    }

    /**
     * @param string $id
     * @return Definition
     */
    public function getDefinition(string $id): Definition
    {
        if (!isset($this->definitions[$id])) {
            throw new ServiceNotFoundException($id);
        }

        return $this->definitions[$id];
    }

    /**
     * @param string $id
     * @param ReflectionMethod $method
     * @param string[] $dependsOn
     * @throws
     * @internal
     */
    public function addInjectableMethod(
        string $id,
        ReflectionMethod $method,
        array $dependsOn = []
    ) {
        $this->logger->debug(
            sprintf(
                'Add injectable method: %s::%s => %s',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
                $id
            )
        );

        $this->injectableMethods[$id] = [
            'method' => $method,
            'dependsOn' => $dependsOn
        ];

        $returnType = $method->getReturnType()
            ? $method->getReturnType()->getName()
            : null;

        if (
            $returnType
            && (class_exists($returnType) || interface_exists($returnType))
        ) {
            $reflector = new ReflectionClass($returnType);
            $this->injectableMethods[$returnType] = $this->injectableMethods[$id];

            foreach ($reflector->getInterfaceNames() as $name) {
                $this->injectableMethods[$name] = $this->injectableMethods[$id];
            }
        }
    }

    /**
     * @param ReflectionProperty $property
     * @param callable $loader 注入时回调的函数
     * @param array $context 上下文数据
     * @internal
     */
    public function addInjectableProperty(
        ReflectionProperty $property,
        callable $loader,
        array $context = []
    ) {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();
        $this->logger->debug(
            sprintf(
                'Add injectable property: %s::$%s => %s',
                $className,
                $propertyName,
                json_encode($context)
            )
        );
        $this->injectableProperties[$className][$propertyName] = [
            'property' => $property,
            'context' => $context,
            'loader' => $loader
        ];
    }

    /**
     * @param string $id
     * @return mixed
     * @throws ReflectionException
     * @throws ServiceNotFoundException
     */
    private function loadInjectableMethod(string $id)
    {
        $this->logger->debug('Loading injectable method: ' . $id);

        /* @var ReflectionMethod $reflection */
        $reflection = $this->injectableMethods[$id]['method'];
        $dependsOn = $this->injectableMethods[$id]['dependsOn'];
        $instance = $this->get($reflection->getDeclaringClass()->getName());

        // 依赖其它服务
        if ($dependsOn) {
            $this->logger->debug(
                'Resolving depends-on',
                [
                    'depends_on' => $dependsOn
                ]
            );
            foreach ($dependsOn as $item) {
                $this->get($item);
            }
        }

        // 参数注入
        $params = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType()->getName();

            $this->logger->debug(sprintf('Resolving parameter: %s %s', $id, $name));

            if ($this->has($name)) {
                $params[] = $this->get($name);
            } elseif ($this->has($type)) {
                $params[] = $this->get($type);
            }
        }

        $result = call_user_func_array([$instance, $reflection->getName()], $params);

        $this->set($id, $result);

        return $result;
    }


}
