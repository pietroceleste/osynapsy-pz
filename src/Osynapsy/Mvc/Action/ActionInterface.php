<?php
namespace Osynapsy\Mvc\Action;

use Osynapsy\Mvc\Controller;

/**
 * Description of ActionInterface
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
interface ActionInterface
{    
    public function getController() : Controller;
    
    public function setController(Controller $controller);
    
    public function setParameters(array $parameters);
}
