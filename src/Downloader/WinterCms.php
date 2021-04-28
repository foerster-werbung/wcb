<?php

namespace FoersterWerbung\Bootstrapper\Winter\Downloader;


use FoersterWerbung\Bootstrapper\Winter\Manager\BaseManager;
use FoersterWerbung\Bootstrapper\Winter\Util\Composer;
use GuzzleHttp\Client;
use FoersterWerbung\Bootstrapper\Winter\Util\CliIO;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class WinterCms extends BaseManager
{
    protected $files = [
        'bootstrap' => '',
        'config' => '',
        'modules' => '',
        'plugins' => '',
        'storage' => '',
        'tests' => '',
        'themes' => '',
        '.htaccess' => '.htaccess',
        'artisan' => 'artisan',
        'composer.json' => 'composer.json',
        'index.php' => 'index.php',
        'server.php' => 'server.php',
    ];

    protected $installerFile;
    protected $wcmsInstallDir = '/tmp/wcs';

    protected $branch = "1.1.x-dev";

    /**
     * Downloads and extracts Winter CMS.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->installerFile = getcwd() . DS . 'installer.php';
    }

    /**
     * Download latest Winter CMS.
     *
     * @param bool $force
     *
     * @return $this
     * @throws RuntimeException
     * @throws LogicException
     */
    public function download($force = false)
    {
        $this->write('-> Checking if Winter CMS download is required...');

        if ($this->alreadyInstalled($force)) {
            throw new \LogicException('-> Winter is already installed. Use --force to reinstall.');
        }

        $this
            ->cleanupProject()
            ->createProject()
            ->copyProjectFiles()
            ->setMaster()
            ->cleanupProject();

        return $this;
    }

    protected function cleanupProject()
    {
        if (is_dir($this->wcmsInstallDir)) {
            $this->write("-> Deleting ocms copy in '$this->wcmsInstallDir'");
            (new Process(sprintf('rm -rf %s', $this->wcmsInstallDir)))->run();
        }

        return $this;
    }

    protected function createProject()
    {
        $this->write("-> Create fresh winter cms copy in ".$this->wcmsInstallDir);
        $this->composer->createProject($this->wcmsInstallDir);

        return $this;
    }

    protected function copyProjectFiles()
    {
        foreach ($this->files as  $src => $dst) {
            $src = $this->wcmsInstallDir . DS . $src;
            $dst = $this->pwd() . $dst;
            $this->write("-> copying ".$src." -> ".$dst);

            (new Process(sprintf('cp -rn %s %s', $src, $dst)))->run();
        }

        return $this;

    }

    /**
     * Since we don't want any unstable updates we fix
     * the libraries to the master branch.
     *
     * @return $this
     */
    protected function setMaster()
    {
        $json = getcwd() . DS . 'composer.json';

        $this->write("-> Changing Winter CMS dependencies to dev-master");

        $contents = file_get_contents($json);

        $contents = preg_replace_callback(
            '/winter\/(?:storm|wn-system-module|wn-backend-module|wn-cms-module)":\s"([^"]+)"/m',
            function ($treffer) {
                $replacedDependency = str_replace($treffer[1], $this->branch, $treffer[0]);
                $this->write("--> $replacedDependency");
                return $replacedDependency;
            },
            $contents
        );

        file_put_contents($json, $contents);

        return $this;
    }

    /**
     * @param $force
     *
     * @return bool
     */
    protected function alreadyInstalled($force)
    {
        if ($force) return false;

        foreach ($this->files as $file => $target) {
            $realFile = getcwd() . DS . $file;
            if (!is_dir($realFile) && !is_file($realFile)) {
                $this->write("-> Missing file or dir '/$file'");
                return false;
            }
        }

        return true;
    }

}
