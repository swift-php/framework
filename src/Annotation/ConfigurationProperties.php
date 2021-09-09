<?php


namespace Swift\Framework\Annotation;


/**
 * @Annotation
 * @Target("CLASS")
 */
class ConfigurationProperties
{

    /**
     * @var string
     */
    public $prefix = '';
}
