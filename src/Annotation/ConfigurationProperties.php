<?php


namespace Tiny\Framework\Annotation;


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