<?php


namespace Swift\Framework\Annotation;


use Reflector;
use ReflectionClass;
use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use Swift\Framework\DependencyInjection\Container;

class ConfigurationAnnotationLoader extends AbstractAnnotationLoader
{

    protected $class = Configuration::class;


    /**
     * @param Reflector $reflection
     * @param object $annotation
     */
    protected function handle(Reflector $reflection, object $annotation)
    {
        // TODO: Implement handle() method.

        $this->logger->debug(json_encode($annotation));
        $container = Container::getInstance();
        if ($reflection instanceof ReflectionClass) {
            $name = $reflection->getName();
            $container->register($name,$name);

        }
    }
}
