<?php


namespace Swift\Framework\Annotation;


use Reflector;
use Swift\Framework\AnnotationLoader\AbstractAnnotationLoader;
use ReflectionClass;


class ConfigurationPropertiesAnnotationLoader extends AbstractAnnotationLoader
{

    protected $class = ConfigurationProperties::class;

    private static $prefixes = [];

    protected function handle(Reflector $reflection, object $annotation)
    {
        // TODO: Implement handle() method.
        if ($reflection instanceof ReflectionClass) {
            self::$prefixes[$reflection->getName()] = $annotation->prefix;
        }
    }

    public static function getPropertiesPrefix(string $class): string
    {
        return self::$prefixes[$class] ?? '';
    }


}
