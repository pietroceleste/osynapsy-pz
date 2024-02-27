<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Assets;

use Osynapsy\Mvc\Controller;

class Loader extends Controller
{
    const CONTENT_TYPE = [
        'js' => 'application/javascript',
        'css' => 'text/css'
    ];

    public function init()
    {        
    }
    
    public function indexAction()
    {            
        $this->template->reset();
        $basePath = __DIR__ . '/../../../assets/';
        $relPath = $this->getParameter(0);
        $fullPath = $basePath . $relPath;
        if (!is_file($fullPath)) {
            return $this->pageNotFound();
        }
        $this->copyFileToCache($this->request->get('page.url'), $fullPath);
        return $this->sendFile($fullPath);
    }                
    
    private function copyFileToCache($webPath, $assetsPath)
    {
        if (file_exists($webPath)) {
            return true;
        }
        $path = explode('/', $webPath);
        $file = array_pop($path);
        $currentPath = './';
        $isFirst = true;
        foreach($path as $dir){
            if (empty($dir)) {
                continue;
            }
            if (!is_writeable($currentPath)) {
                return false;
            }
            $currentPath .= $dir.'/';            
            //If first directory (__assets) not exists or isn't writable abort copy
            if ($isFirst === true && !is_writable($currentPath)) {                
                return false;
            }
            $isFirst = false;
            if (file_exists($currentPath)) {
                continue;
            }
            
            mkdir($currentPath);
        }
        $currentPath .= $file;
        if (!is_writable($currentPath)) {
            return false;
        }
        return copy($assetsPath, $currentPath);
    }
    
    public function pageNotFound()
    {
        ob_clean();
        header('HTTP/1.1 404 Not Found');
        return 'Page not found';        
    }
    
    private function sendFile($filename)
    {
        $offset = 86400 * 7;
        // calc the string in GMT not localtime and add the offset
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        //output the HTTP header
        $this->getResponse()->setHeader('Expires', gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");        
        $this->getResponse()->setContentType(self::CONTENT_TYPE[$ext] ?? 'text/'.$ext);
        return file_get_contents($filename);
    }
}
