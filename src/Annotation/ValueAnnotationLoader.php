<?php


namespace Swift\Framework\Annotation;


use ReflectionProperty;
use Reflector;
use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use Swift\Framework\Config\ConfigManager;
use Swift\Framework\DependencyInjection\Container;
use Tiny\Framework\Annotation\ConfigurationPropertiesAnnotationLoader;

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
                'data'  =>    $reflection->getName(),
                'data1' =>  $reflection->getDeclaringClass()->getName()
            ]);
            $container->addInjectableProperty(
                $reflection,
                function (ReflectionProperty $property, string $value) {
                    $class = $property->getDeclaringClass();
                    $config = ConfigManager::getInstance()->getConfiguration();

                    if (preg_match('/\${.+)(:([^}]+))?}/', $value, $matches)) {
                        $prefix = ConfigurationPropertiesAnnotationLoader::getPropertiesPrefix($class);
                        return $config->getConf(($prefix ? ($prefix . '.') : '') . $matches[1]);
                    }
                    return $value;
                },
                [
                    $annotation->getValue()
                ]
            );
        }
    }

    private function getConfiguration(string $class): Configuration
    {

    }
}
