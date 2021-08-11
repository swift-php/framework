<?php


namespace Swift\Framework\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Injectable
 * @package Swift\Framework\Annotation
 * @Annotation
 * @Target("METHOD")
 */
class Injectable
{

    /**
     * @var string
     */
    public $name = '';

    public function getName(): string
    {
        return $this->name;
    }
}
