<?php

namespace FoersterWerbung\Bootstrapper\Winter\Console;

use InvalidArgumentException;
use LogicException;
use FoersterWerbung\Bootstrapper\Winter\Exceptions\PluginExistsException;
use FoersterWerbung\Bootstrapper\Winter\Manager\PluginManager;
use FoersterWerbung\Bootstrapper\Winter\Util\Artisan;
use FoersterWerbung\Bootstrapper\Winter\Util\CliIO;
use FoersterWerbung\Bootstrapper\Winter\Util\Composer;
use FoersterWerbung\Bootstrapper\Winter\Util\ConfigMaker;
use FoersterWerbung\Bootstrapper\Winter\Util\RunsProcess;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateCommand
 * @package FoersterWerbung\Bootstrapper\Winter\Console
 */
class UpdateCommand extends Command
{
    use ConfigMaker, RunsProcess, CliIO;

    /**
     * @var Artisan
     */
    protected $artisan;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * @var string
     */
    protected $php;

    /**
     * @inheritdoc
     */
    public function __construct($name = null)
    {
        $this->pluginManager = new PluginManager();
        $this->artisan       = new Artisan();
        $this->composer      = new Composer();

        $this->setPhp();

        parent::__construct($name);
    }

    /**
     * Set output for all components
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        $this->pluginManager->setOutput($output);
        $this->composer->setOutput($output);
        $this->artisan->setOutput($output);
    }

    /**
     * Set PHP version to be used in console commands
     */
    public function setPhp(string $php = 'php')
    {
        //IDEA: simple observer for changing the php version
        $this->php = $php;
        $this->artisan->setPhp($php);
        $this->pluginManager->setPhp($php);
    }

    /**
     * Configure the command options.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update Winter CMS.')
            ->addOption(
                'php',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify the path to a custom PHP binary',
                'php'
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws PluginExistsException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareEnv($input, $output);

        $this->makeConfig();

        if ( ! empty($php = $input->getOption('php'))) {
            $this->setPhp($php);
        }

        $this->write("<info>Installing new plugins</info>");

        $pluginsConfigs = $this->config->plugins;

        $this->write("<info>Removing private plugins</info>");
        foreach ($pluginsConfigs as $pluginConfig) {
            list($vendor, $plugin, $remote, $branch) = $this->pluginManager->parseDeclaration($pluginConfig);

            if ( ! empty($remote)) {
                $this->pluginManager->removeDir($pluginConfig);
            }
        }

        $this->write("<info>Cleared private plugins</info>");
        $this->write("<info>Running artisan winter:update</info>");
        $this->artisan->call('winter:update');

        // 4. Git clone all plugins again

        $this->write('<info>Reinstalling plugins:</info>');

        foreach ($pluginsConfigs as $pluginConfig) {
            list($vendor, $plugin, $remote, $branch) = $this->pluginManager->parseDeclaration($pluginConfig);

            if ( ! empty($remote)) {
                $this->pluginManager->install($pluginConfig);
            }
        }

        $this->write('<info>Migrating all unmigrated versions</info>');

        $this->artisan->call('winter:up');

        $this->write('<info>Running composer update</info>');
        $this->composer->updateLock();

        return true;
    }

    /**
     * Prepare the environment
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function prepareEnv(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        $this->pluginManager->setOutput($output);
    }
}
