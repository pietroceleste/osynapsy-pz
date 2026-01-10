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

class RadioList extends Component
{
    private $appendTextBoxAfterLabel = false;
    
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->att('class','osy-radio-list');
    }

    protected function __build_extra__()
    {
        $table = $this->add(new Tag('div'));
        //$dir = $this->getParameter('direction');
        foreach ($this->data as $i => $rec) {
            //Workaround for associative array
            $rec = array_values($rec);
            $tr = $table->add(new Tag('div'));
            $radio = $tr->add(new RadioBox($this->id));
            $radio->att('onclick', "$('#txt_note').removeClass('hidden');")->att('value',$rec[0]);
            $tr->add('&nbsp;&nbsp&nbsp;'.$rec[1]);
        }
        if ($this->appendTextBoxAfterLabel) {
           $tr->add('<input id="txt_note" placeholder="Note" type="textbox" name="'.$this->id.'_text[]" value="" class="form-control'.(!empty($_REQUEST[$this->id]) ? '' : ' hidden d-none').'">');
        }
    }

    public function appendTextBoxAfterLabel($bool)
    {
        $this->appendTextBoxAfterLabel = $bool;
    }
}