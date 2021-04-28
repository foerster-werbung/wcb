<?php

namespace FoersterWerbung\Bootstrapper\Winter\Config;


use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Yaml
 * @package FoersterWerbung\Bootstrapper\Winter\Config
 */
class Yaml implements Config
{
    /**
     * @var mixed
     */
    protected $config;

    /**
     * Yaml constructor.
     *
     * @param             $file
     * @param Parser|null $parser
     *
     * @throws \RuntimeException
     */
    public function __construct($file, Parser $parser = null)
    {
        if ($parser === null) {
            $parser = new Parser();
        }

        try {
            $this->config = $parser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw new \RuntimeException('Unable to parse the YAML string: %s', $e->getMessage());
        }
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function __get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }
}