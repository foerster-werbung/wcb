<?php

namespace FoersterWerbung\Bootstrapper\Winter\Console;

use InvalidArgumentException;
use FoersterWerbung\Bootstrapper\Winter\Util\ManageDirectory;
use FoersterWerbung\Bootstrapper\Winter\Util\UsesTemplate;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitCommand
 * @package FoersterWerbung\Bootstrapper\Winter\Console
 */
class InitCommand extends Command
{
    use UsesTemplate, ManageDirectory;

    /**
     * Configure the command options.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new Winter CMS project.')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Name of the working directory', '.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Creating project directory...</info>');

        $dir = $this->pwd() . $input->getArgument('directory');

        $this->mkdir($dir);

        $output->writeln('<info>Updating template files...</info>');
        $this->updateTemplateFiles();

        $template = $this->getTemplate('winter-cms.yaml');
        $target   = $dir . DS . 'winter-cms.yaml';

        $output->writeln('<info>Creating default winter-cms.yaml...</info>');

        if ($this->fileExists($target)) {
            return $output->writeln('<comment>winter-cms.yaml already exists: ' . $target . '</comment>');
        }

        $this->copy($template, $target);

        $output->writeln('<comment>Done! Now edit your winter-cms.yaml and run winter install.</comment>');

        return true;
    }
}
