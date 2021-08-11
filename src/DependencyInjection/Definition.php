<?php


namespace Swift\Framework\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition as SymfonyDefinition;

class Definition extends SymfonyDefinition
{

    private $instance = null;

    public function setService($instance)
    {
        $this->instance = $instance;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->instance;
    }
}
