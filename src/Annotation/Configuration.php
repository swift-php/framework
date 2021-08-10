<?php
namespace Swift\Framework\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Configuration
 * @package Swift\Framework\Annotation
 * @Annotation
 * @Target("CLASS")
 */
class Configuration
{
    /**
     * @var bool
     */
    public $lazy = true;

    /**
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->lazy;
    }

    /**
     * @param bool $lazy
     */
    public function setLazy(bool $lazy): void
    {
        $this->lazy = $lazy;
    }
}
