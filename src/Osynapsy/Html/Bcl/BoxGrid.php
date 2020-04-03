<?php
namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Component;
use Osynapsy\Html\Tag;

class BoxGrid extends Component
{    
    private $boxWidth = 4;
    private $emptyMessage = 'No data found';    
    private $addComand = false;
    private $panel;    
    
    public function __construct($id)
    {
        parent::__construct('div', $id);        
        $this->panel = $this->add(new PanelNew($id.'Panel'));
        $this->panel->resetClass();
        $this->setClass('osy-gridbox');        
    }
    
    protected function __build_extra__()
	{                
         if (empty($this->data)) {
            $this->appendCell($this->emptyMessage, 12)->setClass('text-center');
            return;
        }
        $i = 12;
        if ($this->addComand) {
            $i -= $this->boxWidth;
        }
        foreach($this->data as $value) {            
            if (empty($i)) {
                $this->getPanel()->addRow()->att('style','margin-top: 10px;');
                $i = 12;
            }
            $this->appendCell($value, $this->boxWidth);
            $i -= $this->boxWidth;
        }    
    }        
        
    public function appendCell($rawValue, $width, $borderStyle = '1px solid silver')
    {        
        $column = $this->getPanel()->addColumn($width);        
        $cellCont = $column->add(new Tag('div', null, 'osy-gridbox-cell'))                           
                           ->att('style','border: '.$borderStyle.'; border-radius: 5px; padding: 5px; 5px;');
        $cellBody = $cellCont->add(new Tag('div', null, 'osy-gridbox-cell-body'));
        $cellFoot = $cellCont->add(new Tag('div', null, 'osy-gridbox-cell-foot clearfix'));
        if (!is_array($rawValue)) {
            $cellBody->add($rawValue);
            $cellFoot->add('&nbsp;');
            return $column;
        }
        $commands = $value = $label = [];
        foreach($rawValue as $key => $val) {
            if ($key[0] !== '_') {
                $value[] = $val;                    
            } elseif ($key === '_cmd') {
                $commands[] = $val;                
            } elseif (strpos($key,'_label') === 0 && !empty($val)) {
                $labelcss = substr($key, 1);
                $label[] = '<span class="label '.$labelcss.'">'.$val.'</span>';
            }                
        }
        $cellBody->add(implode('<br>',$value));
        $cellFoot->add('<div class="pull-left">'.implode('&nbsp;',$label).'</div>');
        $cellFoot->add('<div class="pull-right">'.implode('&nbsp;',$commands).'</div>');
        return $column;
    }        
    
    public function appendAddCommand($command)
    {
        $this->appendCell($command, $this->boxWidth, '1px dashed silver')->setClass('text-center');
        $this->addComand = true;
    }
    
    public function getPanel()
    {
        return $this->panel;
    }
    
    public function setDefaultBoxWidth($width)
    {
        $this->boxWidth = $width;
    }
    
    public function setEmptyMessage($message)
    {
        $this->emptyMessage = $message;
    }
}
