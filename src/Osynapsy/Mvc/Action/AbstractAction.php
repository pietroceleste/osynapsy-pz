<?php
namespace Osynapsy\Mvc\Action;

use Osynapsy\Mvc\Controller;

/**
 * Class to implement response to js action event externally to controller
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
abstract class AbstractAction implements ActionInterface
{
    protected $controller;
    protected $parameters;
    protected $triggers = [];

    protected function executeTrigger($eventId)
    {
        if (empty($this->triggers[$eventId])) {
            return;
        }
        call_user_func($this->triggers[$eventId], $this);
    }

    public function getController() : Controller
    {
        return $this->controller;
    }

    public function getApp()
    {
        return $this->getController()->getApp();
    }

    public function getDb()
    {
        return $this->controller->getDb();
    }

    public function getModel()
    {
        return $this->getController()->getModel();
    }

    public function getParameter($key)
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : null;
    }

    public function getResponse()
    {
        return $this->getController()->getResponse();
    }

    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function setTrigger(array $events, callable $function)
    {
        foreach ($events as $event) {
            $this->triggers[$event] = $function;
        }
    }

    public function raiseException($message, $code = 501)
    {
        throw new \Exception($message, $code);
    }
}
