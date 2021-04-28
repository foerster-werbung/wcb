<?php

namespace FoersterWerbung\Bootstrapper\Winter\Deployment;

use FoersterWerbung\Bootstrapper\Winter\Exceptions\DeploymentExistsException;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Deployment interface
 */
interface DeploymentInterface
{
    /**
     * Install the deployment setup
     *
     * @param boolean $force parameter to enforce installing even if installed
     *
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws DeploymentExistsException
     * @throws LogicException
     */
    public function install($force = false);
}
