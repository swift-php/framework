<?php


namespace Swift\Framework\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Value
 * @package Swift\Framework\Annotation
 * @Annotation
 * @Target("PROPERTY")
 */
class Value
{

    /**
     * @var string
     */
    public $value = '';

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
