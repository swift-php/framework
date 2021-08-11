<?php


namespace Swift\Framework\Configurator;


use Swift\Framework\Annotation\ConfigurationAnnotationLoader;
use Swift\Framework\Annotation\InjectableAnnotationLoader;
use Swift\Framework\AnnotationLoader\AnnotationLoaderManager;
use Swift\Framework\Utils\File;

class Configurator implements ConfiguratorInterface
{

    public function getProperties(): array
    {
        return [
            'annotation' => [
                'description' => 'Annotation configurations',
                'properties' => [
                    'cache' => [
                        'description' => 'Cache settings',
                        'properties' => [
                            'enable' => [
                                'description' => 'Enable annotation cache or not',
                                'valueType' => 'bool',
                                'defaultValue' => true,
                                'required' => false
                            ],
                            'provider' => [
                                'description' => 'Cache provider',
                                'valueType' => 'string',
                                'defaultValue' => 'filesystem',
                                'options' => [
                                    'filesystem'
                                ]
                            ],
                            'filesystem' => [
                                'description' => 'Filesystem cache settings',
                                'properties' => [
                                    'cacheDir' => [
                                        'description' => 'Filesystem cache dir',
                                        'valueType' => 'string',
                                        'defaultValue' => File::resolve('temp/runtime/annotation'),
                                        'required' => false
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'application' => [
                'description' => 'Application configurations',
                'properties' => [
                    /**
                     * @deprecated 2.2.0 将在3.0+版本中移除,使用srcDir代替
                     */
                    'appDir' => [
                        'description' => 'Application logic codes directory - Deprecated',
                        'valueType' => 'string',
                        'defaultValue' => File::resolve('app')
                    ],
                    'srcDir' => [
                        'description' => 'Application source code directory',
                        'valueType' => 'string',
                        'defaultValue' => File::resolve('src')
                    ]
                ]
            ],
            'logger' => [
                'description' => 'Logger settings',
                'valueType' => 'array',
                'properties' => [
                    'default' => [
                        'description' => 'Default logger settings'
                    ]
                ]
            ]
        ];
    }

    public function configure()
    {
        // Register annotation loaders
        $manager = AnnotationLoaderManager::getInstance();

        // DI
        $manager->register(new ConfigurationAnnotationLoader());
//        $manager->register(new ValueAnnotationLoader());
//        $manager->register(new AutowiredAnnotationLoader());
//
//        // Configuration
//        $manager->register(new ConfigurationAnnotationLoader());
        $manager->register(new InjectableAnnotationLoader());
//
//        // Aspect
//        $manager->register(new AspectAnnotationLoader());
//        $manager->register(new PointcutAnnotationLoader());
//        $manager->register(new AroundAnnotationLoader());
//
//        // Component
//        $manager->register(new ComponentAnnotationLoader());
//
//        // Service
//        $manager->register(new ServiceAnnotationLoader());
//
//        // Event
//        $manager->register(new EventListenerAnnotationLoader());
//        $manager->register(new GlobalEventSubscriberAnnotationLoader());
//        $manager->register(new PropertySourceAnnotationLoader());
//        $manager->register(new EventSubscriberAnnotationLoader());
//
//        $manager->register(new ConfigurationPropertiesAnnotationLoader());
    }
}
