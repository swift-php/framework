<?php


namespace Swift\Framework\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Command
 * @package Tiny\Component\ConsoleApplication\Annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class Command
{

    /**
     * @var string
     */
    public $name = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

}
