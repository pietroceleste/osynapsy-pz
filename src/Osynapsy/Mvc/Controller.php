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
use Osynapsy\Http\ResponseJsonOsy;
use Osynapsy\Http\ResponseHtml;
use Osynapsy\Observer\InterfaceSubject;
use Osynapsy\Mvc\Action\ActionInterface;
use Osynapsy\Mvc\ApplicationInterface;

abstract class Controller implements ControllerInterface, InterfaceSubject
{
    use \Osynapsy\Observer\Subject;

    protected $db;
    private $parameters;
    private $dispatcher;
    private $dbFactory;
    private $externalActions = [];
    public $model;
    public $template;
    public $request;
    public $response;
    public $app;

    public function __construct(Request $request = null, DbFactory $db = null, ApplicationInterface $appController = null)
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

    public function run()
    {
        $actionId = filter_input(\INPUT_SERVER, 'HTTP_OSYNAPSY_ACTION');
        return empty($actionId) ? $this->runDefaultAction() : $this->execAction($actionId);
    }

    protected function runDefaultAction()
    {
        $this->setResponse(new ResponseHtml);
        $this->template = new View\Template($this, $this->request->get('page.route')->template);
        if (!method_exists($this, 'indexAction')) {
            throw new \Exception('No method indexAction exists');
        } elseif ($this->model) {
            $this->model->find();
        }
        $this->template->addHtml(autowire()->execute($this, 'indexAction'));
        $this->response->writeStream(strval($this->template));
        return $this->response;
    }

    private function execAction($action)
    {
        $this->setResponse(new ResponseJsonOsy);
        $parameters = empty($_REQUEST['actionParameters']) ? [] : $_REQUEST['actionParameters'];
        if (array_key_exists($action, $this->externalActions)) {
            $this->execExternalAction($action, $parameters);
        } else {
            $this->execInternalAction($action, $parameters);
        }
        return $this->response;
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
        $actionHandle = $this->actionFactory($this->externalActions[$action]);
        $actionHandle->setController($this);
        $actionHandle->setParameters($parameters);
        $message = autowire()->execute($actionHandle, 'execute', $parameters);
        if (!empty($message)) {
            $this->getResponse()->error('alert', $message);
        }
        $this->setState('afterAction'.ucfirst($action));
    }

    private function actionFactory($actionHandle)
    {
        return is_object($actionHandle) ? $actionHandle : autowire()->getInstance($actionHandle);
    }

    private function execInternalAction($cmd, array $parameters)
    {
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

    public function getModel()
    {
        return $this->model;
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

    //public function indexAction(...$args) {}

    abstract public function init();

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

    public function addExternalAction(string $actionClass, $actionId = null)
    {
        $this->externalActions[$actionId ?? sha1($actionClass)] = $actionClass;
    }

    /**
     * Set external class action for manage action
     *
     * @param string $actionName
     * @param string $actionClass
     * @return void
     */
    public function setExternalAction($actionName, ActionInterface $actionClass)
    {
        $this->externalActions[$actionName] = $actionClass;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Open javascript alert on the view
     *
     * @param string $message to show
     *
     */
    public function alert($message)
    {
        $this->js(sprintf("alert(['%s'])", addslashes($message)));
    }

    /**
     * Redirect browser to location in $url parameter indicate
     *
     * @param string $url
     */
    public function go($url)
    {
        $this->response->message('command', 'goto', $url);
    }

    /**
     * Refresh component ids on the view
     *
     * @param array $components
     */
    public function refreshComponents(array $components)
    {
        $this->js(sprintf("Osynapsy.refreshComponents(['%s'])", implode("','", $components)));
    }

    /**
     * Refresh component ids on the parent view
     *
     * @param array $components
     */
    public function refreshParentComponents(array $components)
    {
        $this->js(sprintf("parent.Osynapsy.refreshComponents(['%s'])", implode("','", $components)));
    }

    /**
     * Hide modal $modalId on view
     *
     * @param string $modalId id of the modal to hide
     *
     */
    public function closeModal()
    {
        $this->js(sprintf("parent.$('#%s').modal('hide')", 'amodal'));
    }

    public function historyPushState($id)
    {
        $this->js(sprintf("history.pushState(null,null,'%s');", $id));
    }

    public function js($jscode)
    {
        $this->response->message('command', 'execCode', str_replace(PHP_EOL,'\n', $jscode));
    }
}
