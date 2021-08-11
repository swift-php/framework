<?php


namespace Swift\Framework\DependencyInjection;


class Reference
{
    /**
     * @var string
     */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
    /**
     * @return string The service identifier
     */
    public function __toString()
    {
        return $this->id;
    }
}
