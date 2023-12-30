<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Component;

class Link extends Component
{
    public function __construct($id, $link, $label, $class='')
    {
        parent::__construct('a', $id);        
        $this->att('href', $link)->add($label);
        if ($class) {
            $this->addClass($class);
        }
    }
    
    public function openInModal($title, $widht = '640px', $height = '480px', $postData = false)
    {
        $this->setClass('open-modal' . ($postData ? ' postdata' : ''));
        $this->att([
            'title' => $title,
            'modal-width' => $widht,
            'modal-height' => $height
        ]);
    }
    
    public function setDisabled($condition)
    {
        if (!$condition) {
            return;
        }
        $this->addClass('disabled');
        $this->att('href', '#');
    }
}
