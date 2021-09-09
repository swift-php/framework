<?php


namespace Swift\Framework\Swoole\Command;


use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Swift\Framework\Config\Configuration;
use Swift\Framework\Console\Command\AbstractCommand;
use Swift\Framework\Event\ProcessExitEvent;
use Swift\Framework\EventDispatcher\GlobalEventDispatcher;
use Swift\Framework\Logger\LoggerFactory;
use Swift\Framework\Swoole\Server\ServerInterface;
use Swoole\Runtime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractCommand
{

    const NAME = 'start';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ServerInterface[]
     */
    private $servers = [];

    /**
     * StartCommand constructor.
     * @param string|null $name
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->logger = LoggerFactory::getInstance()->getLogger();
    }

    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Start swoole servers');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Configuration::getInstance();

        GlobalEventDispatcher::addListener(
            ProcessExitEvent::class,
            [$this, 'exit']
        );

        $list = $config->getConf('swoole.servers');
        if (!empty($list)) {
            $this->logger->info(sprintf('Starting %d server(s)...', count($list)));
            foreach ($list as $options) {
                /* @var ServerInterface$server */
                $server = new $options['type']($options);
                $this->servers[] = $server;
                $server->run();
            }
        }

        return 0;
    }

    public function exit()
    {
        if (!empty($this->servers)) {
            $this->logger->info('Closing servers...');
            foreach ($this->servers as $server) {
                $server->close();
            }
            $this->logger->info('Servers closed');
        }
    }
}
