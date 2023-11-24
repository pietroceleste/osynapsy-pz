<?php
use Osynapsy\Kernel;
use Osynapsy\Helper\AutoWire;

/**
 * If $elementPath is null return request Object else return required element of request
 *
 * @param string $elementPath
 * @return mixed
 */
function request($elementPath = null)
{
    return is_null($elementPath) ? Kernel::$request : Kernel::$request->get($elementPath);
}

/**
 * Return an instance of AutoWire class
 *
 * @param array $handles array of object instanced used how handles from autowire
 * @return AutoWire
 */
function autowire(array $handles = [])
{
    return new AutoWire($handles);
}

/**
 * Return an instance of App class
 *
 * @return Dbo
 */
function app()
{
    return AutoWire::getHandle(ApplicationInterface::class);
}

/**
 * Return an instance of Dbo class
 *
 * @return Dbo
 */
function dbo()
{
    return AutoWire::getHandle(DboInterface::class);
}

/**
 * Redirect client browser to $rawdirection url
 * if first character of $rawdestination is a '#' build a route.
 *
 * @param string $rawdestination
 * @param array $getParams
 * @param array $routeParams
 */
function redirect($rawdestination, array $getParams = [], array $routeParams = [])
{
    $destination = ($rawdestination[0] === '#') ? route(ltrim($rawdestination, '#'), $routeParams) : $rawdestination;
    $url = sprintf('%s%s', $destination, !empty($getParams) ? '?' . http_build_query($getParams) : '');
    if (request()->hasHeader("X-Osynapsy-Action")) {
        response()->go($url);
        return;
    }
    header('Location: '.$url);
}

/**
 * if routeId parameter is null return current route else return ruote with routeId.
 * If array parameters is passed build relative url of the specified route
 *
 * @param string $routeId
 * @param array|null $parameters
 * @return mixed
 */
function route($routeId = null, array $parameters = null, array $getParams = [])
{
    $route = request()->getRoute($routeId);
    return is_null($parameters) ? $route : $route->getUrl($parameters, $getParams);
}

/**
 * Return session object or session value key
 *
 * @param string $key
 * @return mixed
 */
function session($key = null)
{
    $session = AutoWire::getHandle(AppSession::class);
    return is_null($key) ? $session : $session($key);
}

function debug($value = null, $backtraceLevel = 2)
{
    $backtrace = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT, $backtraceLevel);
    $class = $backtrace[1]['class'] ?: "No class";
    $function = $backtrace[1]['function'] ?: "No function";
    $line =  $backtrace[1]['line'] ?: 'no line number';
    $debug = [
        sprintf('%s - %s', date('Y-m-d H:i:s') , sprintf('%s->%s line %s', $class , $function, $line)),
        sprintf('%s - %s', date('Y-m-d H:i:s') , is_string($value) ? $value :  str_replace(PHP_EOL, '\\n', print_r($value, true)))
    ];
    header('X-Console-Debug: ' .implode("\\n", $debug));
}
