<?php

namespace Swift\Framework\Logger;
use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use Swift\Component\Singleton;
use Swift\Framework\Config\Configuration;

class LoggerFactory
{

    use Singleton;

    /**
     * @var LoggerInterface[]
     */
    private $loggers = [];

    /**
     * @param string $name
     * @param array $options
     * @return LoggerInterface
     */
    public function getLogger(string $name = 'default',array $options = []): LoggerInterface
    {
        try {
            if (!isset($this->loggers[$name])) {
                if (empty($options)) {
                    $options = Configuration::getInstance()->getConf('logger.' . $name);
                }
                $logger = new Logger($name);

                if (empty($options['handlers'])) {
                    $options['handlers'][] = [
                        'class' => StreamHandler::class,
                        'constructorArgs' => [
                            'php://stdout',
                            LogLevel::DEBUG
                        ]
                    ];
                }

                foreach ($options['handlers'] as $item) {
                    $reflection = new ReflectionClass($item['class']);
                    /* @var HandlerInterface $handler*/
                    if (isset($item['constructorArgs'])) {
                        $handler = $reflection->newInstanceArgs($item['constructorArgs']);
                    } else {
                        $handler = $reflection->newInstance();
                    }
                    if (isset($item['formatter'])) {
                        $formatData = $item['formatter'];
                        /* @var FormatterInterface $formatter*/
                        $formatter = null;
                        if (is_array($formatData)) {
                            $reflection = new ReflectionClass($formatData['class']);
                            $args = $formatData['constructorArgs'];
                            if (!empty($args)) {
                                $formatter = $reflection->newInstanceArgs($args);
                            } else {
                                $formatter = $reflection->newInstance();
                            }
                        } else if(is_string($formatData)) {
                            $formatter = new $formatData;
                        }
                        $handler->setFormatter($formatter);
                    }

                    $logger->pushHandler($handler);
                }
                $this->loggers[$name] = $logger;
            }
        }catch (\Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return $this->loggers[$name];
    }

}
