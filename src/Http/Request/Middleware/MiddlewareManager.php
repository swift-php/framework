<?php


namespace Swift\Framework\Http\Request\Middleware;


use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Swift\Framework\DependencyInjection\Container;
use Swift\Framework\Exception\ServiceNotFoundException;
use Swift\Framework\Logger\LoggerFactory;

class MiddlewareManager
{
    /**
     * @var array
     */
    private static $middleware = [];

    /**
     * @var MiddlewareInterface[]
     */
    private static $stacks = [];

    /**
     * @var Container
     */
    private static $container = null;

    /**
     * @var LoggerInterface
     */
    private static $logger = null;

    public static function registerMiddleware(string $middleware,
                                              string $class = '',
                                              string $method = '',
                                              int $priority = 0)
    {
        $container = Container::getInstance();

        if (!$container->has($middleware)) {
            $container->register($middleware, $middleware);
        }
        self::$logger = LoggerFactory::getInstance()->getLogger();
        self::$middleware[$priority][] = [
            'class' => $class,
            'method' => $method,
            'middleware' => $middleware
        ];

        krsort(self::$middleware);
    }

    /**
     * @param string $class
     * @param string $method
     * @return array
     * @throws ReflectionException
     * @throws ServiceNotFoundException
     */
    public static function getStack(string $class = '', string $method = ''): array
    {
        $name = $class . '::' . $method;
        $stack = self::$stacks[$name] ?? [];

        if (empty($stack)) {
            $container = Container::getInstance();

            foreach (self::$middleware as $priority => $list) {
                foreach ($list as $item) {
                    if (
                        ($item['class'] === $class && $item['method'] === $method) ||
                        ($item['class'] === $class && !$item['method'])
                    ) {
                        $stack[] = $container->get($item['middleware']);
                    }
                }
            }

            if ($class && $method) {
                self::$logger->info('ActionDispatchingMiddleware-----------');
//                $stack[] = Action
            } else {
                self::$logger->info('DispatchingMiddleware-----------');
                $stack[] = new DispatchingMiddleware();
            }

            self::$stacks[$name] = $stack;
        }

        return $stack;
    }
}
