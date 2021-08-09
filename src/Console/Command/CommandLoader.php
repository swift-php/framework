<?php

namespace Swift\Framework\Console\Command;


use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class CommandLoader implements CommandLoaderInterface
{
    /**
     * @var Container
     */
    private $container = null;
    /**
     * @param $name
     * @return mixed
     * @throws
     */
    public function get(string $name)
    {
//        $list = $this->container->findTaggedServiceIds('console.command');
//
//        foreach ($list as $id => $attrs) {
//            if ($attrs[0]['command'] === $name) {
//                return $this->container->get($id);
//            }
//        }
//
//        throw new CommandNotFoundException('Command not found');
    }

    /**
     * @inheritDoc
     */
    public function has(string $name)
    {
//        return in_array($name, $this->getNames());
    }

    /**
     * @return string[]|void
     */
    public function getNames()
    {
//        $names = [];
//        $list = $this->container->findTaggedServiceIds('console.command');
//        foreach ($list as $commandName => $attrs) {
//            $names[] = $attrs[0]['command'];
//        }
//
//        return $names;
    }
}
