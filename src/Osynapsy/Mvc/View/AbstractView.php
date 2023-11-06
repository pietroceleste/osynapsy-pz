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
    protected $components = array();
    protected $controller;
    protected $reponse;
    protected $db;    
    
    public function __construct(Controller $controller, $title = null)
    {
        $this->controller = $controller;
        $this->request = $controller->request;
        $this->db = $controller->getDb();        
        if ($title) {
            $this->setTitle($title);
        }
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
        return $this->getController()->model;
    }
    
	public function getDb()
    {
        return $this->getController()->getDb();
    }
	
    public function setTitle($title)
    {
        $this->getController()->getResponse()->addContent($title,'title');
    }
    
    public function addJs($path)
    {    
        $this->getController()->getResponse()->addJs($path);
    }
    
    public function addCss($path)
    {    
        $this->getController()->getResponse()->addCss($path);
    }
    
    public function addJsCode($code)
    {
        $this->getController()->getResponse()->addJsCode($code);
    }
    
    public function addStyle($style)
    {
        $this->getController()->getResponse()->addStyle($style);
    }
    
    public function __toString()
    {
        return $this->get();
    }
    
    protected function componentFactory(array $componentIds)
    {
        $this->getController()->getResponse()->resetTemplate();
        $this->getController()->getResponse()->resetContent();
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
}
