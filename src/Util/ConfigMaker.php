<?php

namespace FoersterWerbung\Bootstrapper\Winter\Util;

use FoersterWerbung\Bootstrapper\Winter\Config\Yaml;
use RuntimeException;

/**
 * Config maker trait
 */
trait ConfigMaker
{
    use ManageDirectory;

    /**
     * @var
     */
    public $config;

    protected function makeConfig()
    {
        $configFile = $this->pwd() . 'winter-cms.yaml';
        if ( ! file_exists($configFile)) {
            throw new RuntimeException("<comment>winter-cms.yaml not found. Run winter init first.</comment>", 1);
        }

        $this->config = new Yaml($configFile);
    }
}
