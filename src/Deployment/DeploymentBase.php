<?php

namespace FoersterWerbung\Bootstrapper\Winter\Deployment;

use FoersterWerbung\Bootstrapper\Winter\Util\ManageDirectory;
use FoersterWerbung\Bootstrapper\Winter\Util\UsesTemplate;

/**
 * Deployment base class
 */
abstract class DeploymentBase
{
    use UsesTemplate, ManageDirectory;
}
