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
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Html\Tag;

class ListBox extends Component
{
    public $data = array();
    private $hdn;
    private $box;
    
    public function __construct($id)
    {
        $this->requireJs('Bcl/ListBox/script.js');
        $this->requireCss('Bcl/ListBox/style.css');
        parent::__construct('div', $id.'_container');
        $this->att('class','listbox');
        $this->hdn = $this->add(new HiddenBox($id));
        $this->box = $this->add(new Tag('div', null, 'listbox-box'));
    }
    
    protected function __build_extra__()
    {        
        $list = $this->add(new Tag('ul', null, 'listbox-list'));        
        foreach ($this->data as $rec) {
            $selected = '';
            if (array_key_exists($this->hdn->id, $_REQUEST) && ($rec[0] == $_REQUEST[$this->hdn->id])) {
                $this->box->set($rec[1]);
                $selected = ' selected';
            }
            $list->add(new Tag('li'))                
                 ->add(new Tag('div', null, 'listbox-list-item'.$selected))
                 ->att('value',$rec[0])                 
                 ->add($rec[1]);
        }
    }
    
    public function setAction($action, $parameters = null, $class = 'click-execute', $confirm = null)
    {
        $this->hdn->att([
           'class' => 'change-execute',
            'data-action' => $action,
            'data-action-parameters' => $parameters,
            'data-confirm' => $confirm
        ]);
    }
    
    public function SetData($data)
    {
        $this->data = $data;
    }
    
    public function onChange($code)
    {
        $this->hdn->att('onchange', $code);
    }
}
