<?php 

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Data;

class Dictionary implements \ArrayAccess, \Iterator, \Countable
{   
    public $repo = [];
    
    public function __construct(array $init = null)
    {
        $this->repo = $init;
    }
    
    public function __invoke($key)
    {
        return $this->get($key);
    }
    
    public function __call($method, $args)
    {
        throw new Exception('Dictionary class - Recall inexistent method :'.$method);
    }

    private function addValue($key, $value, $append = false)
    {
        $ksearch = explode('.',$key);        
        $klast   = count($ksearch)-1;
        $target  =& $this->repo;
        
        foreach ($ksearch as $i => $k) {
            if ($klast == $i) {
                if (!$append) {
                    $target[$k] = $value;                   
                } elseif (is_array($target[$k])) {
                    $target[$k] += $value;
                } else {
                    $target[$k] = array($value);
                }
            } elseif (is_array($target) && array_key_exists($k, $target)) {
                $target = &$target[$k];
            } elseif(count($ksearch) != ($i+1)) {
                $target[$k] = array(); 
                $target =& $target[$k];
            } 
        }
        
        return $this;
    }
    
    public function append()
    {
        $args = func_get_args();
        $value = array_pop($args);
        $key = implode('.', $args);
        $this->addValue($key, $value, true);
        return $this;
    }
    
    public function  buildKey()
    {
        return implode('.', func_get_args());
    }
    
    public function &get($key) 
    {
        if (empty($key)) {
            return $this->repo;
        }
        $ksearch = explode('.', $key);
        $target =& $this->repo;
        foreach ($ksearch as $k) { 
            if (!array_key_exists($k, $target)) {
                $app = null;
                return $app;
            } 
            $target =& $target[$k];
        }
        return $target;
    }
     
    public function set()
    {
        $args = func_get_args();
        $value = array_pop($args);
        $key = implode('.', $args);
        $this->addValue($key, $value);
        return $this;
    }
    
    public function keyExists($key)
    {
        $ksearch = explode('.',$key);
        $target = $this->repo;
        $nnode = count($ksearch);
        foreach($ksearch as $k) {
            if (!is_array($target)) { 
                break;
            } 
            if (array_key_exists($k, $target)){
                $target = $target[$k];
            } else {
                break;
            }
            $nnode--;
        }
        return $nnode ? false : true;
    }
    
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->repo[] = $value;
        } else {
            $this->repo[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->repo[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->repo[$offset]);
    }

    public function &offsetGet($offset) 
    {
        $null = null;
        return isset($this->repo[$offset]) ? $this->get($offset) : $null;
    }
    
    public function rewind()
    {
        reset($this->repo);
    }

    public function current()
    {
        return current($this->repo);
    }

    public function key()
    {
        return key($this->repo);
    }

    public function next()
    {
        return next($this->repo);
    }

    public function valid()
    {
        return $this->current() !== false;
    }    

    public function count()
    {
        return count($this->repo);
    }
    
    public function search($keySearch, $searchPath = null, &$result = [])
    {
        $data = is_array($searchPath) ? $searchPath : $this->get($searchPath); 
        if (empty($data)) {
            return [];
        }        
        foreach($data as $key => $value){            
            if ($key === $keySearch) {
                $result += $value;
            } elseif (is_array($value)) {
                $this->search($keySearch, $value, $result);
            }            
        }        
        return  $result;
    }
    
    public static function flatternize($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $plain = array();
        foreach ($array as $key => $value) {
            if(is_array($value)){
                $plain = array_merge($plain, self::flatternize($value));
            } else {
                $plain[] = $value;
            }	
        }
        return $plain;
    }
}
