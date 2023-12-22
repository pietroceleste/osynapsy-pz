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

class PanelNew extends Component
{
    private $sections = array(
        'head' => null,
        'body' => null,
        'foot' => null
    );
    
    private $classCss = [
        'main' => 'panel',
        'head' => 'panel-heading',
        'body' => 'panel-body',
        'foot' => 'panel-footer'
    ];
    protected $title;
    private $currentRow = null;
    private $currentColumn = null;
    
    public function __construct($id, $title='', $class = ' panel-default', $tag = 'div')
    {
        parent::__construct($tag, $id);
        $this->classCss['main'] = 'panel'.$class;
        if (!empty($title)) {
            $this->setTitle($title);
        }
        $this->sections['body'] = new Tag('div');        
    }
    
    protected function __build_extra__()
    {
        $this->att('class', $this->classCss['main']);
        foreach ($this->sections as $key => $section){
            if (empty($section)) {
                continue;
            }
            $section->att('class', $this->classCss[$key]);
            $this->add($section);
        }
    }
    
    public function addRow()
    {
        $this->currentRow = $this->sections['body']->add(new Tag('div', null, 'row'));
        return $this->currentRow;
    }

    public function addClass($class)
    {
        $this->classCss['main'] .= ' ' . $class;
    }

    public function addColumn($colspan = 12, $offset = 0)
    {
        if (empty($this->currentRow)) {
            $this->addRow();
        }
        $this->currentColumn = $this->currentRow->add(new Column($colspan, $offset));
        return $this->currentColumn;
    }
    
    public function getBody()
    {
        return $this->sections['body'];
    }
    
    public function pushHorizontalField($label, $field, $info = '', $labelColumnWidth = 3)
    {        
        $row = $this->addRow()->att('class', 'form-group');
        $offset = $labelColumnWidth;
        if (!empty($label)) {
            $row->add(new Tag('label', null, sprintf('col-sm-%s control-label', $labelColumnWidth)))->add($label);
            $offset = 0;
        }
        if (!empty($label) && is_object($field)) {
            $field->att('data-label', strip_tags($label));
        }
        $fieldContainer = $row->add(new Tag('div', null, sprintf('col-sm-%s col-sm-offset-%s', 12 - $labelColumnWidth, $offset)));
        $fieldContainer->add($field);
        if (!empty($info)) {
            $fieldContainer->add(new Tag('div'))->add($info);
        }
        $this->classCss['main'] = 'form-horizontal';
    }
        
    public function resetClass()
    {
        $this->setClass('','','','');
    }
    
    public function setClass($body, $head = null, $foot = null, $main = null)
    {
        $this->classCss['body'] = $body;
        if (!is_null($head)) {
            $this->classCss['head'] = $head;
        }
        if (!is_null($foot)) {
            $this->classCss['foot'] = $foot;
        }
        if (!is_null($main)) {
            $this->classCss['main'] = $main;
        }        
        return $this;
    }

    public function setTitle($title)
    {
        $this->sections['head'] = new Tag('div');
        $this->sections['head']->add('<h4 class="panel-title">'.$title.'</h4>');
    }
}
