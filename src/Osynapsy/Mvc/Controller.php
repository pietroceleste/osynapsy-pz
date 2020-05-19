<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Mvc;

use Osynapsy\Db\DbFactory;
use Osynapsy\Event\Dispatcher;
use Osynapsy\Http\Request;
use Osynapsy\Http\Response;
use Osynapsy\Http\ResponseJson as JsonResponse;
use Osynapsy\Http\ResponseHtmlOcl as HtmlResponse;
use Osynapsy\Observer\InterfaceSubject;
use Osynapsy\Mvc\Action\InterfaceAction;

abstract class Controller implements InterfaceController, InterfaceSubject
{
    use \Osynapsy\Observer\Subject;

    protected $db;
    private $parameters;
    private $dispatcher;
    private $dbFactory;
    private $externalActions = [];
    public $model;
    public $request;
    public $response;
    public $app;

    public function __construct(Request $request = null, DbFactory $db = null, $appController = null)
    {
        $this->parameters = $request->get('page.route')->parameters;
        $this->request = $request;
        $this->setDbHandler($db);
        $this->app = $appController;
        $this->dispatcher = new Dispatcher($this);
        $this->loadObserver();
        $this->setState('init');
        $this->init();
        $this->setState('initEnd');
    }

    public function deleteAction()
    {
        if ($this->model) {
            $this->model->delete();
        }
    }

    /**
     * Recall and execute an external action class
     *
     * @param string $action
     * @param array $parameters
     * @return \Osynapsy\Http\Response
     */
    private function execExternalAction($action, array $parameters)
    {
        $this->setState('beforeAction'.ucfirst($action));
        $actionInstance = $this->externalActions[$action];
        $actionInstance->setController($this);
        $actionInstance->setParameters($parameters);
        $actionInstance->execute();
        $this->setState('afterAction'.ucfirst($action));
    }

    private function execInternalAction($cmd, array $parameters)
    {
        //$cmd = $_REQUEST[$this->actionKey];
        //sleep(0.7);
        $this->setState($cmd.'ActionStart');
        if (!method_exists($this, $cmd.'Action')) {
            $res = 'No action '.$cmd.' exist in '.get_class($this);
        } elseif (!empty($parameters)){
            $res = call_user_func_array([$this, $cmd.'Action'], $parameters);
        } else {
            $res = $this->{$cmd.'Action'}();
        }
        $this->setState($cmd.'ActionEnd');
        if (!empty($res) && is_string($res)) {
            $this->response->error('alert',$res);
        }
    }

    private function execAction($action)
    {
        $this->setResponse(new JsonResponse());
        $parameters = empty($_REQUEST['actionParameters']) ? [] : $_REQUEST['actionParameters'];
        if (array_key_exists($action, $this->externalActions)) {
            $this->execExternalAction($action, $parameters);
        } else {
            $this->execInternalAction($action, $parameters);
        }
        return $this->response;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function getDb($key = 0)
    {
        return $this->dbFactory->getConnection($key);
    }

    public function getDbFactory()
    {
        return $this->dbFactory;
    }

    public function getDispacther()
    {
        return $this->dispatcher;
    }

    public function getParameter($key)
    {
        if (!is_array($this->parameters)) {
            return null;
        }
        if (!array_key_exists($key, $this->parameters)) {
            return null;
        }
        if ($this->parameters[$key] === '') {
            return null;
        }
        return $this->parameters[$key];
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getState()
    {
        return $this->state;
    }

    abstract public function indexAction();

    abstract public function init();

    public function loadView($path, $params = array(), $return = false)
    {
        $view = $this->response->getBuffer($path, $this);
        if ($return) {
            return $view;
        }
        $this->response->addContent($view);
    }

    public function run()
    {
        $cmd = filter_input(\INPUT_SERVER, 'HTTP_OSYNAPSY_ACTION');
        if (!empty($cmd)) {
            return $this->execAction($cmd);
        }
        $this->setResponse(new HtmlResponse());
        $layoutPath = $this->request->get('page.route')->template;
        if (!empty($layoutPath)) {
            $this->response->template = $this->response->getBuffer($layoutPath, $this);
        }
        if ($this->model) {
            $this->model->find();
        }
        $resp = $this->indexAction();
        if ($resp) {
            $this->response->addContent($resp);
        }
        return $this->response;
    }

    public function saveAction()
    {
        if ($this->model) {
            $this->model->save();
        }
    }

    public function setDbHandler($dbFactory)
    {
        $this->dbFactory = $dbFactory;
        $this->db = $this->dbFactory->getConnection(0);
    }

    /**
     * Set external class action for manage action
     *
     * @param string $actionName
     * @param string $actionClass
     * @return void
     */
    public function setExternalAction($actionName, InterfaceAction $actionClass)
    {
        $this->externalActions[$actionName] = $actionClass;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
