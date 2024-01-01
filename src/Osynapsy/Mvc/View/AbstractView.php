<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Mvc\View;

use Osynapsy\Mvc\Controller;
use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;

abstract class AbstractView
{    
    protected $controller;        
    protected $properties = [];
    
    public function __construct(Controller $controller, array $properties = [])
    {
        $this->controller = $controller;
        $this->properties = $properties;
    }
    
    abstract public function init();
    
    public function get()
    {
        $view = $this->init();
        return empty($_REQUEST['ajax']) || !is_array($_REQUEST['ajax']) ? $this->viewFactory($view) : $this->componentFactory($_REQUEST['ajax']);
    }

    public function getController()
    {
        return $this->controller;
    }
    
    public function getModel()
    {
        return $this->controller->getModel();
    }
    
	public function getDb()
    {
        return $this->controller->getDb();
    }

    public function getResponse()
    {
        return $this->controller->getResponse();
    }

    public function setTitle($title)
    {
        $this->getResponse()->addContent($title,'title');
    }
    
    public function addJs($path)
    {    
        $this->getResponse()->addJs($path);
    }
    
    public function addCss($path)
    {    
        $this->getResponse()->addCss($path);
    }
    
    public function addJsCode($code)
    {
        $this->getResponse()->addJsCode($code);
    }
    
    public function addStyle($style)
    {
        $this->getResponse()->addStyle($style);
    }
    
    public function __toString()
    {
        return strval($this->get());
    }
    
    protected function componentFactory(array $componentIds)
    {
        $this->getResponse()->resetTemplate();
        $this->getResponse()->resetContent();
        $response = new Tag('div', 'response');
        foreach ($componentIds as $id) {
            $response->add(Component::getById($id));
        }
        return $response;
    }

    protected function viewFactory($view)
    {
        $requires = Component::getRequire();
        if (!empty($requires)) {
            $this->appendLibToResponse($requires);
        }
        return $view;
    }

    protected function appendLibToResponse($libRequires)
    {
        foreach ($libRequires as $type => $urls) {
            foreach ($urls as $url){
                switch($type) {
                    case 'js':
                        $this->addJs($url);
                        break;
                    case 'jscode':
                        $this->addJsCode($url);
                        break;
                    case 'css':
                        $this->addCss($url);
                        break;
                }
            }
        }
    }

    public function __get($propertyId)
    {
        return array_key_exists($propertyId, $this->properties) ? $this->properties[$propertyId] : null;
    }

    public function __set($propertyId, $value)
    {
        $this->properties[$propertyId] = $value;
    }

    public function __isset($propertyId)
    {
        return !empty($this->properties[$propertyId]);
    }

    public function __invoke(array $properties = [])
    {
        $this->properties += $properties;
    }
}
