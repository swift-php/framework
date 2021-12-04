<?php


namespace Swift\Framework\Http\Request\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class DispatchingMiddleware implements MiddlewareInterface
{
    /**
     * @var PathDispatcher
     */
    private $pathDispatcher;

    public function __construct()
    {
        $this->pathDispatcher = new PathDispatcher();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ReflectionException
     * @throws ServiceNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->pathDispatcher->dispatch($request);

        $middleware = MiddlewareManager::getStack(
            $result->getControllerName(),
            $result->getActionName()
        );

        $request = $request->withAttribute('dispatchingResult', $result);
        $request = $request->withAttribute('middleware', $middleware);

        return $handler->handle($request);
    }
}
