<?php


namespace Swift\Framework\Annotation;


use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use Reflector;
use Swift\Framework\DependencyInjection\Container;
use ReflectionMethod;
class InjectableAnnotationLoader extends AbstractAnnotationLoader
{

    protected $class = Injectable::class;


    /**
     * @param Reflector $reflection
     * @param object $annotation
     */
    protected function handle(Reflector $reflection, object $annotation)
    {
        // TODO: Implement handle() method.

        $this->logger->debug(json_encode($annotation));
        $container = Container::getInstance();
        if ($reflection instanceof ReflectionMethod) {
            $name = $annotation->getName() ?: $reflection->getName();
            $container->addInjectableMethod($name, $reflection);
        }
    }
}
