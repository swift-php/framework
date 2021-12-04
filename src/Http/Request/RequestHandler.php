<?php


namespace Swift\Framework\Http\Request;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Swift\Framework\Http\Request\Middleware\DefaultMiddleware;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\UUID;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MiddlewareInterface
     */
    private $defaultMiddleware;

    /**
     * HttpRequestHandler constructor.
     * @throws
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
        $this->defaultMiddleware = new DefaultMiddleware();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute('request-id', UUID::v4());

        /* @var MiddlewareInterface[] $stack */
        $stack = $request->getAttribute('middleware');
        if ($stack === null) {
            $this->logger->debug(sprintf(
                'Handle http request: %s %s HTTP/%s',
                $request->getMethod(),
                $request->getUri()->getPath(),
                $request->getProtocolVersion()
            ));

            $this->logger->debug('Using middleware: ' . DefaultMiddleware::class);

            return $this->defaultMiddleware->process($request, $this);
        } else {
            $middleware = array_shift($stack);

            $this->logger->debug(sprintf('Using middleware: %s', get_class($middleware)));

            return $middleware->process(
                $request->withAttribute('middleware', $stack),
                $this
            );
        }
    }
}
