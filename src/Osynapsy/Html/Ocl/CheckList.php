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

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;

class CheckList extends Component
{
    private $table = null;
    private $values = array();
    private $groups = array();
    private $appendTextBoxAfterLabel = false;
    
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->att('class','osy-check-list');
    }

    protected function __build_extra__()
    {
        $this->table =  $this->add(new Tag('table'));
        foreach ($this->values as $k => $value) {
            $this->buildRow($value);
        }
    }
    
    protected function buildRow($value, $lev=0)
    {
        $tr = $this->table->add(new Tag('tr'));
        if (!empty($_REQUEST[$this->id]) && is_array($_REQUEST[$this->id]) && in_array($value[0],$_REQUEST[$this->id])) {
            $value['selected'] = true;
        }
        $td = $tr->add(new Tag('td'));
        $td->add(str_repeat('&nbsp;',$lev*7).'<input type="checkbox" class="i-checks" onclick="$(this).prop(\'checked\') == true ? $(this).closest(\'td\').find(\'.form-control\').removeClass(\'hidden\') : $(this).closest(\'td\').find(\'.form-control\').addClass(\'hidden\').val(\'\');" name="'.$this->id.'[]" value="'.$value[0].'"'.(!empty($value['selected']) ? ' checked' : '').'>&nbsp;'.$value[1]);
        if ($this->appendTextBoxAfterLabel) {            
            $td->add('<br><input type="textbox" name="'.$this->id.'_text[]" value="" class="form-control'.(!empty($value['selected']) ? '' : ' hidden d-none').'">');
        }
        if (!empty($this->groups[$value[0]])) {
            $lev += 1;
            foreach($this->groups[$value[0]] as $k => $value) {
                $this->buildRow($value, $lev);    
            }
        }
    }
    
    public function setValues($data, $tree=false)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }
        foreach($data as $k => $rec) {
            if (empty($rec['_group'])) {
                $this->values[] = $rec;
            } else {
                $this->groups[$rec['_group']][] = $rec;
            }
        }
    }
    
    public function setDatasource($source, $db=null)
    {
        if (empty($db)){
            $trasform = array();
            if (is_array($source)){
                foreach($source as $k => $v) {
                    $trasform[] = [0 => $k, 1 => $v];
                }
                $source = $trasform;
            }
            $this->values = $source;
            return $this;
        }
        $this->values = $db->execQuery($source,null,'NUM');
    }
    
    public function setHeight($px)
    {
        $this->style = 'height: '.$px.'px; border: 1px solid black; overflow: auto;';
    }
    
    public function appendTextBoxAfterLabel($bool)
    {
        $this->appendTextBoxAfterLabel = $bool;
    }
}
