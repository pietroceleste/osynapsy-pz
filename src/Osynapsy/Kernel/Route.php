<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Kernel;

/**
 * Description of Route
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Route 
{
    private $route = [
        'id' => null,
        'uri' => null,
        'application' => null,
        'controller' => null,
        'template' => null,
        'parameters' => []
    ];
    
    public function __construct($id = '', $uri = '', $application = '', $controller = '', $template = '',array $attributes = [])
    {
        $this->id = empty($id) ? sha1($uri) : $id;
        $this->uri = $uri;
        $this->application = trim($application);
        $this->controller = trim(str_replace(':','\\',$controller));
        $this->template = $template;
        $this->route += $attributes;
    }
    
    public function __get($key)
    {
        return array_key_exists($key, $this->route) ? $this->route[$key] : null;
    }
    
    public function __set($key, $value)
    {
        $this->route[$key] = $value;
    }
    
    public function __toString()
    {
        return $this->id;
    }

    public function getParameter($idx)
    {        
        return array_key_exists($idx, $this->route['parameters']) ? $this->route['parameters'][$idx] : null;
    }

    public function getUrl(array $segmentParams = [], array $getParams = [])
    {
        $output = $result = [];
        preg_match_all('/\?.?/', $this->uri, $output);
        if (count($output[0]) > count($segmentParams)) {
            throw new \Exception('Number of parameters don\'t match uri params');
        }        
        //$url = str_replace($output[0], $segmentParams, $this->uri);
        $url = $this->string_replace($this->uri, $output[0], $segmentParams);        
        $url .= !empty($getParams) ? '?' : '';
        $url .= http_build_query($getParams);
        return $url;
    }

    private function string_replace($stringRaw, $placeholders, $values)
    {
        $result = $stringRaw;
        foreach($placeholders as $i => $placeholder) {
            $placeholderPos = strpos($result, $placeholder);
            if ($placeholderPos !== false) {
                $segment =  $values[$i];
                $result = substr_replace($result, $segment, $placeholderPos, strlen($placeholder));
            }
        }
        return $result;
    }
    
    public static function createFromArray(array $route)
    {
        return new Route($route['id'], $route['path'], null, $route['@value'], $route['template'], $route);
    }
}
