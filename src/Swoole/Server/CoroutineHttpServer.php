<?php


namespace Swift\Framework\Swoole\Server;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Swift\Framework\Http\Request\RequestHandler;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Utils\Options;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
class CoroutineHttpServer implements ServerInterface
{

    private $options = [
        'host' => '127.0.0.1',
        'port' => 3000,
        'ssl'  => false,
        'options' => []
    ];

    /**
     * @var Server
     */
    private $server = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * CoroutineHttpServer constructor.
     *
     * @param array $options
     * @throws ReflectionException
     */
    public function __construct(array $options = [])
    {
        $this->logger = LoggerFactory::getInstance()->getLogger();
        $this->setOptions($options);
        $this->handler = new RequestHandler();
    }

    public function setOptions(array $options)
    {
        $this->options = Options::merge($this->options, $options);
    }

    public function run()
    {
        $this->logger->info(sprintf(
            'Starting http server (listening on %s:%s) ...',
            $this->options['host'],
            $this->options['port']
        ));

        $this->server = new Server(
            $this->options['host'],
            $this->options['port'],
            $this->options['ssl']
        );

        $this->server->set($this->options['options']);

        $this->server->handle('/', function (Request $swRequest, Response $swResponse) {
            $serverParams = [];

            foreach ($swRequest->server as $name => $value) {
                $serverParams[strtoupper($name)] = $value;
            }



            $protocol = substr($serverParams['SERVER_PROTOCOL'], 5);

            if ($protocol === '1.1') {
                $data = $swRequest->getData();
                preg_match('/\r\n\r\n(.+)$/sm', $data, $matches);
                $body = $matches[1] ?? null;
            } else {
                $data = null;
                $body = $swRequest->rawContent();
            }

            $request = new ServerRequest(
                $serverParams['REQUEST_METHOD'],
                $serverParams['REQUEST_URI'],
                $swRequest->header,
                $body,
                $protocol,
                $serverParams
            );

            if ($queryParams = $swRequest->get) {
                $request = $request->withQueryParams($queryParams);
            }
            if ($swRequest->post) {
                $request = $request->withParsedBody($swRequest->post);
            }

            if ($files = $swRequest->files) {
                $uploadedFiles = [];
                foreach ($files as $name => $file) {
                    $uploadedFiles[$name] = new UploadedFile(
                        $file['tmp_name'],
                        $file['size'],
                        $file['error'],
                        $file['name'],
                        $file['type']
                    );
                }
                $request = $request->withUploadedFiles($uploadedFiles);
            }

            $response = $this->handler->handle($request);
            $swResponse->status($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $value) {
                foreach ($value as $v) {
                    $swResponse->setHeader($name, $v);
                }
            }
            $response->getBody()->rewind();
            $swResponse->end($response->getBody()->getContents());

        });

        $this->server->start();
    }

    public function close()
    {
        $this->server->shutdown();
        $this->logger->info('Http server closed');
    }

}
