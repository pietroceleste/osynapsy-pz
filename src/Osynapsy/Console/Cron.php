<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Console;

use Osynapsy\Kernel\Loader;
use Osynapsy\Kernel\Route;
use Osynapsy\Kernel;

/**
 * Description of Cron
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Cron
{
    private $argv;
    private $script;
    private $vendorDir;
    private $rootDir;
    private $appDirs = [];

    public function __construct($vendorDir, array $argv)
    {
        $this->vendorDir = $vendorDir;
        $this->rootDir = realpath($this->vendorDir . '/../');
        $this->script = array_shift($argv);
        $this->argv = $argv;
        $this->discoverOsyApplicationDirectories();
    }

    /**
     * Metodo che scopre i file di configurazione delle app registrate nei file istanza
     */
    protected function discoverOsyApplicationDirectories()
    {
        $instanceConfigurationDir = $this->rootDir. '/etc/';
        $d = dir($instanceConfigurationDir);
        do {
            $file = $d->read();
            $instanceFilePath = $instanceConfigurationDir . '/' . $file;
            $xml = $this->loadInstanceConfiguration($instanceFilePath);
            if (empty($xml)) {
                continue;
            }
            $appId = $xml->app->children()->getName();
            $this->appDirs[$appId] = [$instanceFilePath, $this->vendorDir .'/'. str_replace('_', '/', $appId) . '/etc'];
        } while ($file);
        $d->close();
    }

    protected function loadInstanceConfiguration($instanceFilePath)
    {
        return is_file($instanceFilePath) ? simplexml_load_file($instanceFilePath) : false;
    }

    public function run()
    {
        foreach($this->appDirs as $appId => list($instanceFile, $appDir)) {
            $appConfiguration = $this->loadAppConfiguration($appDir . '/config.xml');
            if (empty($appConfiguration) || !is_array($appConfiguration)) {
                continue;
            }
            $cronJobs = $this->loadCronJobs($appConfiguration);
            if (!empty($cronJobs)) {
                $this->exec($appId, $instanceFile, $cronJobs);
            }
        }
    }

    private function loadAppConfiguration($appConfFilePath)
    {
        return (new Loader($appConfFilePath))->get();
    }

    private function loadCronJobs($appConfiguration)
    {
        if (empty($appConfiguration['cron'])) {
            return [];
        }
        $rawjobs = array_values($appConfiguration['cron'])[0];
        return array_combine(
            array_column($rawjobs, 'id'),
            array_column($rawjobs, '@value')
       );
    }

    private function exec($appId, $instanceFile, $appJobs)
    {
        foreach($appJobs as $jobId => $jobController){
             $jobRoute = new Route($jobId, null, $appId, $jobController);
             echo (new Kernel($instanceFile))->followRoute($jobRoute);
        }
    }
}
