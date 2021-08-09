<?php

namespace Swift\Framework\Config;
use Swift\Component\Singleton;
use Swift\Spl\AbstractConfig;
use Swift\Spl\SplArrayConfig;

class Configuration
{
    private $conf;
    use Singleton;

    public function __construct(?AbstractConfig $config = null)
    {
        if ($config == null) {
            $config = new SplArrayConfig();
        }
        $this->conf = $config;
    }

    public function getConf($path = null)
    {
        return $this->conf->getConf($path);
    }

    public function setConf(string $path, array $data): bool
    {
        return $this->conf->setConf($path, $data);
    }

    public function loadFile(string $filePath, bool $merge = true)
    {
        if (file_exists($filePath)) {
            $confData = require_once $filePath;
            if (is_array($confData)) {
                if ($merge) {
                    $this->conf->merge($confData);
                } else {
                    $this->conf->load($confData);
                }
            }

        }
        return false;
    }

    public function setProperties(array $props)
    {
        $config = $this->getPropertyValues($props, []);
        $this->conf->merge($config);
    }

//    public function getProperties(): array
//    {
//        return $this->properties;
//    }

    private function getPropertyValues(array $props, array $config)
    {
        foreach ($props as $k => $v) {
            if (!array_key_exists($k, $config)) {
                $config[$k] = $v['defaultValue'] ?? null;
                if (isset($v['properties'])) {
                    $config[$k] = [];
                    $config[$k] = $this->getPropertyValues($v['properties'], $config[$k]);
                }
            }
        }
        return $config;
    }

}
