<?php
namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Component;
use Osynapsy\Html\Tag;

class BoxGrid extends Component
{
    private $boxWidth = 4;
    private $emptyMessage = '';
    private $addComand;
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
            if (!empty($this->emptyMessage)) {
                $this->appendCell($this->emptyMessage, 12)->setClass('text-center');
            }
            return;
        }
        $i = 0;
        foreach($this->data as $value) {
            if ($i >= 12) {
                $this->addRowToPanel();
                $i = 0;
            }
            $this->appendCell($value, $this->boxWidth);
            $i += $this->boxWidth;
        }
        if ($i >= 12) {
            $this->addRowToPanel();
        }
        if ($this->addComand) {
            $this->appendCell($this->addComand, $this->boxWidth, '1px dashed silver')->setClass('text-center');
        }
    }

    protected function addRowToPanel()
    {
        $this->getPanel()->addRow()->att('style','margin-top: 10px !important;');
    }

    public function appendCell($rawValue, $width, $borderStyle = '1px solid silver')
    {
        $column = $this->getPanel()->addColumn($width);
        $column->add($this->cellFactory($rawValue, $borderStyle));
        return $column;
    }

    protected function cellFactory($rawValue, $borderStyle)
    {
        $cellCont = new Tag('div', null, 'osy-gridbox-cell');
        $cellCont->att('style','border: '.$borderStyle.'; border-radius: 5px; padding: 5px; 5px;');
        $cellBody = $cellCont->add(new Tag('div', null, 'osy-gridbox-cell-body'));
        $cellFoot = $cellCont->add(new Tag('div', null, 'osy-gridbox-cell-foot clearfix'));
        if (!is_array($rawValue)) {
            $cellBody->add($rawValue);
            $cellFoot->add('&nbsp;');
            return $cellCont;
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
        return $cellCont;
    }

    public function appendAddCommand($command)
    {
        $this->addComand = $command;
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
