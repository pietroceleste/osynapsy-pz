<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Http;

/**
 * Description of ResponseOsyJson
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class ResponseJsonOsy extends ResponseJson
{
    public function __construct()
    {
        parent::__construct();
        if (empty(ob_get_level())) {
            ob_start();
        }
    }
    
    /**
     * Store a error message
     * 
     * If recall without parameter return if errors exists.
     * If recall with only $oid parameter return if error $oid exists
     * If recall it with $oid e $err parameter set error $err on key $oid.
     * 
     * @param string $oid
     * @param string $err
     * @return type
     */
    public function error($oid = null, $err = null)
    {
        if (is_null($oid) && is_null($err)){
            return array_key_exists('errors', $this->streams);
        }
        if (!is_null($oid) && is_null($err)){
            return array_key_exists('errors', $this->streams) && array_key_exists($oid, $this->streams['errors']);
        }         
        if (function_exists('mb_detect_encoding') && !mb_detect_encoding($err, 'UTF-8', true)) {        
            $err = \utf8_encode($err);
        }
        $this->message('errors', $oid, $err);
    }
    
    /**
     * Prepare a goto message for FormController.js
     * 
     * If $immediate = true dispatch of the response is immediate     
     * 
     * @param string $url
     * @param bool $immediate
     */
    public function go($url)
    {
        $this->message('command', 'goto', $url);        
    }

    /**
     * Append a generic messagge to the response
     * 
     * @param string $streamId
     * @param string $targetId
     * @param string $value
     */
    public function message($streamId, $targetId, $value)
    {        
        $this->writeStream([$targetId, $value], $streamId);
    }
    
    public function js($cmd)
    {
        $this->message('command', 'execCode', str_replace(PHP_EOL,'\n',$cmd));
    }

    public function historyPushState($id)
    {
        $this->js(sprintf("history.pushState(null,null,'%s');", $id));
    }
    
    public function __toString()
    {        
        $this->writeStream(ob_get_clean(), 'debug');
        return parent::__toString();
    }
}
