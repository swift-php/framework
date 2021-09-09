<?php

namespace Swift\Framework\Console\Command;


use Psr\Container\ContainerInterface;
use Swift\Framework\DependencyInjection\Container;
use Swift\Framework\DependencyInjection\Definition;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandLoader implements CommandLoaderInterface
{
    /**
     * @var Container
     */
    private $container = null;

    public function getContainer() : Container
    {
        return $this->container;
    }

    public function setContainer(?ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $name
     * @return mixed
     * @throws
     */
    public function get(string $name)
    {
        $list = $this->container->findTaggedServiceIds('console.command');

        foreach ($list as $id => $attrs) {
            if ($attrs[0]['command'] === $name) {
                return $this->container->get($id);
            }
        }

        throw new CommandNotFoundException('Command not found');
    }

    /**
     * @param string $name
     * @param string $class
     * @return Definition
     */
    public function register(string $name,string $class):Definition
    {
        return $this->container->register($name,$class)
                        ->addTag('console.command', ['command'  =>  $name]);
    }

    /**
     * @inheritDoc
     */
    public function has(string $name)
    {
        return in_array($name, $this->getNames());
    }

    /**
     * @return string[]|void
     */
    public function getNames()
    {
        $names = [];
        $list = $this->container->findTaggedServiceIds('console.command');
        foreach ($list as $commandName => $attrs) {
            $names[] = $attrs[0]['command'];
        }

        return $names;
    }
}
