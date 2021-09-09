<?php


namespace Swift\Framework\Annotation;


use ReflectionClass;
use Reflector;
use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use Swift\Framework\Bootstrap\Bootstrap;
use Swift\Framework\Console\Command\CommandLoader;

class CommandAnnotationLoader extends AbstractAnnotationLoader
{
    protected $class = Command::class;


    /**
     * @param Reflector $reflection
     * @param object $annotation
     */
    protected function handle(Reflector $reflection, object $annotation)
    {
        // TODO: Implement handle() method.

        $this->logger->debug(json_encode($annotation));
        if ($reflection instanceof ReflectionClass) {
            $commandName = $annotation->getName() ?: $reflection->getShortName();
            $application = Bootstrap::getInstance()->getApplication();
            /* @var CommandLoader $commandLoader*/
            $commandLoader = $application->getCommandLoader();
            $commandLoader->register($commandName, $reflection->getName())->addMethodCall('setName', [$commandName]);
        }
    }
}
