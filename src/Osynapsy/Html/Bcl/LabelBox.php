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

class LabelBox extends Component
{
    protected $hiddenBox;
    protected $label;
    
    public function __construct($id, $label='')
    {
        $this->requireCss('Bcl/LabelBox/style.css?v=1.0');
        parent::__construct('div', $id.'_labelbox');
        $this->att('class','osynapsy-labelbox');
        $this->hiddenBox = $this->add(new HiddenBox($id));
        $this->add($label);
    }
    
    public function setValue($value)
    {
        if (!empty($_REQUEST[$this->hiddenBox->id])) {
            return $this;
        }
        $_REQUEST[$this->hiddenBox->id] = $value;
        return $this;
    }
    
    public function setLabelFromSQL($db, $sql, $par=array())
    {
        $this->label = $db->execUnique($sql, $par);
    }
    
    public function setLabel($label)
    {        
        $this->label = !is_array($label) ? $label : $this->searchLabelInArray($label);
        return $this;
    }
    
    protected function searchLabelInArray($labels)
    {
        $value = $_REQUEST[$this->hiddenBox->id] ?? null;        
        if (empty($value)) {
            return '';
        }
        $ids = array_column($labels, 0);
        $labelIdx = array_search($value, $ids);
        if ($labelIdx===false) {
            return '';
        }
        return $labels[$labelIdx][1];
    }
    
    public function __build_extra__()
    {
        if (is_null($this->label)) {
            $label = isset($_REQUEST[$this->hiddenBox->id]) ? $_REQUEST[$this->hiddenBox->id] : null;
        } else {
            $label = $this->label;
        }
        $this->add('<span>'.$label.'</span>');
    }
}
