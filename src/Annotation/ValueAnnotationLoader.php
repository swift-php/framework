<?php


namespace Swift\Framework\Annotation;


use ReflectionProperty;
use Reflector;
use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use Swift\Framework\DependencyInjection\Container;

class ValueAnnotationLoader extends AbstractAnnotationLoader
{
    protected $class = Value::class;

    protected function handle(Reflector $reflection, object $annotation)
    {
        /* @var Container $container */
        $container = Container::getInstance();
        /* @var Value $annotation */
        if ($reflection instanceof ReflectionProperty) {
            $this->logger->info('property name',[
                'data'  =>    $reflection->getValue(),
                'data1' =>  $annotation->getValue()
            ]);
            $reflection->getName();
            $annotation->getValue();
//            $container->addInjectableProperty(
//                $reflection,
//                $annotation->getValue(),
//                function (ReflectionProperty $property, string $value) {
//                    $class = $property->getDeclaringClass();
//                    $config = $this->getConfiguration($class);
//
//                    // @Value("${property.key:defaultValue}")
//                    if (preg_match('/\${(.+)(:([^}]+))?}/', $value, $matches)) {
//                        $prefix = ConfigurationPropertiesAnnotationLoader
//                            ::getPropertiesPrefix($class);
//
//                        return $config->get(
//                            ($prefix ? ($prefix . '.') : '') . $matches[1],
//                            $matches[3] ?? null
//                        );
//                    }
//
//                    return $value;
//                }
//            );
        }
    }
}
