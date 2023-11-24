<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy;

use Osynapsy\Http\Request;
use Osynapsy\Kernel\Loader;
use Osynapsy\Kernel\Route;
use Osynapsy\Kernel\Router;
use Osynapsy\Kernel\Runner;
use Osynapsy\Kernel\KernelException;

/**
 * The Kernel is the core of Osynapsy
 * 
 * It init Http request e translate it in response
 *
 * @author Pietro Celeste <p.celeste@osynapsy.org>
 */
class Kernel
{
    const VERSION = '0.4.1-DEV';
    
    public $router;
    public static $request;
    public $controller;
    public $appController;
    private $loader;    
    private $composer;
    
    /**
     * Kernel costructor
     * 
     * @param string $fileconf path of the instance configuration file
     * @param object $composer Instance of composer loader
     */
    public function __construct($fileconf, $composer = null)
    {                
        $this->composer = $composer;
        $this->loader = new Loader($fileconf);
        self::$request = $this->requestFactory();
    }

    protected function requestFactory()
    {
        $request = new Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
        $request->set('app.parameters', $this->loadConfig('parameter', 'name', 'value'));
        $request->set('env', $this->loader->get());
        $request->set('app.layouts', $this->loadConfig('layout', 'name', 'path'));
        $request->set('observers', $this->loadConfig('observer', '@value', 'subject'));
        $request->set('listeners', $this->loadConfig('listener', '@value', 'event'));
        return $request;
    }
    
    private function loadConfig($key, $name, $value)
    {
        $array = $this->loader->search($key);
        $result = [];
        foreach($array as $rec) {
            $result[$rec[$name]] = $rec[$value];
        }
        return $result;
    }
    
    /**
     * Load in router object all route of application present in config file
     */
    private function loadRoutes()
    {        
        $this->router = new Router(self::$request);
        $this->router->addRoute(
            'OsynapsyAssetsManager',
            '/assets/osynapsy/'.self::VERSION.'/?*',
            'Osynapsy\\Assets\\Loader',
            '',
            'Osynapsy'
        );
        $applications = $this->loader->get('app');
        if (empty($applications)) {
            throw new KernelException('No app configuration found');
        }
        foreach(array_keys($applications) as $applicationId) {
            $routes = $this->loader->search('route', "app.{$applicationId}");
            foreach ($routes as $route) {
                if (!isset($route['path'])) {
                    continue;
                }
                $id = isset($route['id']) ? $route['id'] : uniqid();
                $uri = $route['path'];
                $controller = $route['@value'];
                $template = !empty($route['template']) ? self::$request->get('app.layouts.'.$route['template']) : '';
                $this->router->addRoute($id, $uri, $controller, $template, $applicationId, $route);                
            }
        }        
    }
    
    /**
     * Run process to get response starting to request uri
     * 
     * @param string $requestUri is Uri requested from 
     * @return string 
     */
    public function run($requestUri = null)
    {
        if (is_null($requestUri)) {
            $requestUri = strtok(filter_input(INPUT_SERVER, 'REQUEST_URI'),'?');
        }
        $this->loadRoutes();
        return $this->followRoute(
            $this->router->dispatchRoute($requestUri)
        );
    }
    
    /**
     * 
     * @param Route $route
     * @return string
     */
    public function followRoute(Route $route)
    {
        self::$request->set('page.route', $route);
        $runner = new Runner(self::$request, $route);
        return $runner->run();  
    }
}
