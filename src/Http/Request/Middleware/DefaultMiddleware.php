<?php


namespace Swift\Framework\Http\Request\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swift\Framework\Exception\ConfigurationKeyNotFoundException;

class DefaultMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ConfigurationKeyNotFoundException
     */
    public function process(ServerRequestInterface $request,
                            RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request = $request->withAttribute('middleware',);
            return $handler->handle($request);
        }catch (\Throwable $throwable) {

        }
    }

}
