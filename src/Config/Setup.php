<?php

namespace FoersterWerbung\Bootstrapper\Winter\Config;


use FoersterWerbung\Bootstrapper\Winter\Util\KeyGenerator;
use FoersterWerbung\Bootstrapper\Winter\Util\RunsProcess;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Setup
 * @package FoersterWerbung\Bootstrapper\Winter\Config
 */
class Setup
{
    use RunsProcess;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Writer
     */
    protected $writer;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var string
     */
    protected $php;

    /**
     * Setup constructor.
     *
     * @param Config          $config
     * @param OutputInterface $output
     */
    public function __construct(Config $config, OutputInterface $output, $php)
    {
        $this->config = $config;
        $this->output = $output;
        $this->writer = new Writer();
        $this->php    = $php;
    }

    /**
     * Put env() calls into config files.
     *
     * @return void
     */
    public function config()
    {
        $this->app();
        $this->cms();
        $this->theme();
        $this->mail();
    }

    /**
     * Write .env files.
     *
     * @param bool $createBackup
     * @param bool $restoreBackup
     *
     * @return $this
     */
    public function env($createBackup = true, $restoreBackup = false)
    {
        $newEnv = $this->writer->backupExistingEnv();

        // Remove to be able to run winter:env
        $this->writer->removeCurrentEnv();

        $this->runProcess($this->php . ' artisan winter:env', 'Failed to create env config!');

        $lines = [
            'APP_DEBUG'       => (bool)$this->config->app['debug'] ? 'true' : 'false',
            'APP_URL'         => $this->config->app['url'],
            'APP_KEY'         => (new KeyGenerator())->generate(),
            'APP_ENV'         => 'dev',
            '',
            'CMS_SAFE_MODE'   => $this->getSafeMode(),
            '',
            'DB_CONNECTION'   => $this->config->database['connection'],
            'DB_HOST'         => $this->config->database['host'],
            'DB_PORT'         => $this->config->database['port'],
            'DB_DATABASE'     => $this->config->database['database'],
            'DB_USERNAME'     => $this->config->database['username'],
            'DB_PASSWORD'     => $this->config->database['password'],
            '',
            'REDIS_HOST'      => '127.0.0.1',
            'REDIS_PASSWORD'  => 'null',
            'REDIS_PORT'      => '6379',
            '',
            'CACHE_DRIVER'    => 'file',
            'SESSION_DRIVER'  => 'file',
            'QUEUE_DRIVER'    => $this->config->queue['driver'] ? $this->config->queue['driver'] : 'sync',
            '',
            'MAIL_DRIVER'     => $this->config->mail['driver'],
            'MAIL_HOST'       => '"' . $this->config->mail['host'] . '"',
            'MAIL_PORT'       => '587',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_USERNAME'   => null,
            'MAIL_PASSWORD'   => null,
            'MAIL_NAME'       => '"' . $this->config->mail['name'] . '"',
            'MAIL_ADDRESS'    => $this->config->mail['address'],
            '',
            'ASSETS_CACHE'    => 'false',
            'ROUTES_CACHE'    => 'false',
            'LINK_POLICY'     => 'detect',
            'ENABLE_CSRF'     => 'true',
        ];

        // Remove the template generated by winter:env,
        // we'll generate our own version below
        $this->writer->removeCurrentEnv();

        foreach ($lines as $key => $value) {
            $this->writer->writeEnvFile($key, $value);
        }

        if ($newEnv !== false) {
            if ($restoreBackup) {
                $this->writer->restore($newEnv);
            }

            if ( ! $createBackup) {
                unlink($newEnv);
            }
        }

        $this->writer->createEnvExample();
        $this->writer->createEnvProduction();

        return $this;
    }

    /**
     * Write the app configuration.
     *
     * @return void
     */
    protected function app()
    {
        $values = [
            'locale' => $this->config->app['locale'],
        ];

        $this->writer->write('app', $values);
    }

    /**
     * Write the cms configuration.
     *
     * @return void
     */
    protected function cms()
    {
        $values = [
            'enableSafeMode' => "env('CMS_SAFE_MODE', " . $this->getSafeMode() . ')',
        ];

        $this->writer->write('cms', $values);
    }

    /**
     * Set the default theme.
     *
     * @return boolean
     */
    protected function theme()
    {
        try {
            $activeTheme = explode(' ', $this->config->cms['theme']);
        } catch (\RuntimeException $e) {
            // No theme set
            return false;
        }

        $values = [
            'activeTheme' => $activeTheme[0],
        ];

        $this->writer->write('cms', $values);

        return true;
    }

    /**
     * Write the mail configuration.
     *
     * @return void
     */
    protected function mail()
    {
        // Replace the inline 'address/name' config entry separately
        // since this edge case is not supported by the generic
        // Writer->write method.
        $contents = file_get_contents($this->writer->filePath('mail'));

        $regex   = "/'address'\s+=>\s+'[^']+',\s+\'name\'\s+=>\s+'[^']+'/";
        $replace = "'address' => env('MAIL_ADDRESS'), 'name' => env('MAIL_NAME')";

        file_put_contents($this->writer->filePath('mail'), preg_replace($regex, $replace, $contents));
    }

    /**
     * Since the enableSafeMode property can be null, true or false
     * we need to be careful when parsing it to a string.
     *
     * @return string
     */
    protected function getSafeMode()
    {
        $safeMode = 'null';
        if (array_key_exists('enableSafeMode', $this->config->cms)) {
            if ($this->config->cms['enableSafeMode'] !== null) {
                $safeMode = $this->config->cms['enableSafeMode'] === true ? 'true' : 'false';
            }
        }

        return $safeMode;
    }
}