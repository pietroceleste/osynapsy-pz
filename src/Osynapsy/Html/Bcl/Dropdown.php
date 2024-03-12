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

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;
use Osynapsy\Html\Ocl\HiddenBox;

class Dropdown extends Component
{
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';

    private $list;
    private $button;
    
    public function __construct($name, $label, $align = 'left', $tag = 'div')
    {
        parent::__construct($tag);
        $this->addClass('dropdown')->add(new HiddenBox($name));
        $this->button = $this->add($this->buttonFactory($name.'_btn', $label));
        $this->list = $this->add($this->ulFactory($name, $align));
    }

    protected function buttonFactory($id, $label)
    {
        $Button = new Button($id, 'button', 'dropdown-toggle', sprintf('%s <span class="caret"></span>', $label));
        $Button->att([
            'data-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'false'
        ]);
        return $Button;
    }

    protected function ulFactory($name, $align)
    {
        $ul = new Tag('ul', null , 'dropdown-menu dropdown-menu-'.$align);
        $ul->att('aria-labelledby', $name);
        return $ul;
    }
    
    protected function __build_extra__()
    {
        foreach ($this->data as $key => $rec) {
            if (empty($rec)) {
                continue;
            }
            if (is_object($rec)) {
                $this->list->att('data-value',$key)->add(new Tag('li'))->add($rec);
                continue;
            }
            if ($rec === 'divider') {
                $this->list->add(new Tag('li'))->att(['class' => 'divider','role' => 'separator']);
                continue;
            }
            $rec = array_values($rec);
            $this->list
                 ->add(new Tag('li'))
                 ->att('data-value',$rec[0])
                 ->add('<a href="#">'.$rec[1].'</a>');
        }
    }
            
    public function getButton()
    {
        return $this->button;
    }
}
