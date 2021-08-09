<?php


namespace Swift\Framework\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition as SymfonyDefinition;

class Definition extends SymfonyDefinition
{

    private $instance = null;

    public function setInstance($instance)
    {
        $this->instance = $instance;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }
}
