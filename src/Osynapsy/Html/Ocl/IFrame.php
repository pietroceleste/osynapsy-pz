<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Ocl;

use Osynapsy\Html\Component;

//Field iframe
class IFrame extends Component
{

    public function __construct($name){
        parent::__construct('iframe',$name);
        $this->att('name',$name);
        $this->att('style', 'width: 100%; height: 75vh; border: 0px; border: 1px solid #ddd');
    }

    protected function __build_extra__(){
        $src = $this->getParameter('src');
        if (!array_key_exists($this->id,$_REQUEST) && !empty($src)){
            $_REQUEST[$this->id] = $src;
        }
        if(array_key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id])){
            $this->att('src',$_REQUEST[$this->id]);
        }
    }
}
