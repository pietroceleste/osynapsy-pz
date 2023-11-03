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
use Osynapsy\Html\Tag;

class FormGroup extends Component
{
    public $label;
    public $object;

    public function __construct($object, $label = '&nbsp;', $class = 'form-group')
    {
        parent::__construct('div');
        $this->att('class', $class);
        $this->label = $label;
        $this->object = $object;
    }
    
    public function __build_extra__()
    {
        if (!empty($this->label)) {
            $this->add($this->labelContainerFactory(is_array($this->label) ? $this->label : [$this->label]));
        }
        $this->add($this->object);
    }

    protected function labelContainerFactory(array $rawLabel)
    {        
        $strLabel = array_shift($rawLabel);
        $labelContainer = new Tag('div');
        $labelContainer->add($this->labelFactory($strLabel));        
        if (!empty($rawLabel)) {            
            $labelContainer->add($this->commandBoxFactory($rawLabel));
        }        
        return $labelContainer;
    }

    protected function labelFactory($strLabel)
    {
        $label = new Tag('label');
        $label->add($strLabel);
        if (is_object($this->object)) {
            $label->att('for', $this->object->id);
        }
        return $label;
    }

    protected function commandBoxFactory($commands)
    {
        $cmdContainer = new Tag('div', null, 'float-right pull-right');
        foreach($commands as $cmd) {
            $cmdContainer->add($cmd);
        }
        return $cmdContainer;
    }
}
