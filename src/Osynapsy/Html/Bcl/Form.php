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
use Osynapsy\Data\Dictionary;
use Osynapsy\Html\Bcl\Column;
use Osynapsy\Html\Bcl\Alert;
use Osynapsy\Html\Bcl\Panel;

/**
 * Represents a Html Form.
 *
 * @author Pietro Celeste <p.celeste@osynapsy.org>
 */
class Form extends Component
{
    private $head;
    public  $headClass = 'row';
    private $alert;
    private $alertCount=0;
    private $body;
    private $foot;
    private $repo;
    private $appendFootToMain = false;

    public function __construct($name, $bodyComponentClass = Panel::class, $tag = 'form')
    {
        parent::__construct($tag, $name);
        $this->repo = new Dictionary(array(
           'foot' => array(
                'offset' => 1,
                'width' => 10
            )
        ));
        $this->att('name',$name)->att('method','post')->att('role','form');
        $this->body = $this->bodyFactory($bodyComponentClass, $name);
    }

    protected function bodyFactory($bodyComponentClass, $name)
    {
        //Start workaround
        if ($bodyComponentClass === 'Tab') {
            $bodyComponentClass = Tab::class;
        }
        //End workaround
        $this->appendFootToMain = ($bodyComponentClass === Panel::class);
        $body = new $bodyComponentClass($name.'_panel');
        $body->setParameter('label-position', 'inside');
        return $body;
    }

    protected function __build_extra__()
    {
        if ($this->head) {
            $this->add(new Tag('div', null, 'osy-mb-10 m-b'))
                 ->add(new Tag('div', null, $this->headClass))
                 ->add($this->head);
        }

        if ($this->alert) {
            $this->add($this->alert);
        }

        $this->add($this->body);
        //Append foot
        if (!$this->foot) {
            return;
        }
        if ($this->appendFootToMain) {
            $this->body->put(
                '',
                $this->foot->get(),
                10000,
                10,
                $this->repo->get('foot.width'),
                $this->repo->get('foot.offset')
            );
            return;
        }
        $this->add($this->foot->get());
    }


    public function head($width=12, $offset = 0)
    {
        //Head setting
        if (empty($this->head)) {
            $this->head = new Tag('dummy');
        }
        $column = $this->head->add(new Column($width, $offset));
        return $column;
    }

    public function alert($label, $type='danger')
    {
        if (empty($this->alert)) {
            $this->alert = new Tag('div');
            $this->alert->att('class','transition animated fadeIn m-b-sm');
        }
        $icon = '';
        switch ($type) {
            case 'danger':
                $icon = '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>';
                break;
        }
        $alert = new Alert('al'.$this->alertCount, $icon.' '.$label, $type);
        $alert->att('class','alert-dismissible text-center',true)
              ->add(' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
        $this->alert->add($alert);
        $this->alertCount++;
        return $this->alert;
    }

    public function foot($obj)
    {
        if (empty($this->foot)) {
            $this->foot = new Tag('div');
            $this->foot->att('class','clearfix');
        }
        $this->foot->add($obj);
        return is_object($obj) ? $obj : $this->foot;
    }

    public function getPanel()
    {
        return $this->body;
    }

    public function setCommand($delete = false, $save = true, $back = true, $modalClose = false, $footOnBottom = false)
    {
        if ($save) {
            $this->foot(new Button('btn_save', 'button', 'btn-primary pull-right'))
                 ->setAction('save')
                 ->att('style','min-width: 100px; margin-right: 10px;')
                 ->add($save === true ? '<span class="glyphicon glyphicon-floppy-disk"></span> Salva' : $save);
        }

        if ($delete) {
            $this->foot(new Button('btn_delete', 'button', 'btn-danger pull-right'))
                 ->setAction('delete')
                 ->att('data-confirm', 'Sei sicuro di voler eliminare il record corrente?')
                 ->att('style','min-width: 100px; margin-right: 10px;')
                 ->add('<span class="glyphicon glyphicon-trash"></span> Elimina');
        }

        if ($back) {
            $this->foot(new Button('btn_back'))
                 ->att('class','cmd-back btn btn-default pull-left')
                 ->att('style','margin-right: 10px; min-width: 100px;')
                 ->add('<span class="glyphicon glyphicon-chevron-left"></span> Indietro');
        }
        if ($modalClose) {
            $this->foot(new Button('btn_modal_close', 'button', 'btn btn-default pull-left float-left', '<i class="fa fa-times"></i> Chiudi'))
                 ->att('style','margin-right: 10px; min-width: 100px;')
                 ->att('onclick',"parent.$('#amodal').modal('hide');");
        }
        if (!empty($this->foot) && !empty($footOnBottom)) {
            $this->getPanel()->addClass('osy-mb-50');
            $this->foot->addClass('osy-fixed-bottom');
        }
    }

    public function setType($type)
    {
        if ($type == 'horizontal') {
            $this->att('class','form-horizontal',true);
        }
        $this->body->setType($type);
    }

    public function setTitle($title, $subTitle = null, $size = 6)
    {
        $objTitle = new Tag('h2', null, 'font-light m-t-2');
        $objTitle->add($title);
        $column = $this->head($size);
        $column->att('style', 'padding: 0px');
        $column->push(false, $objTitle, false);
        if (!empty($subTitle)) {
            $column->push(false, is_string($subTitle) ? '<h4><i>'.$subTitle.'</i></h4>' : $subTitle,false);
        }
        return $column;
    }

    public function parameter($key, $value=null)
    {
        if (is_null($value)){
            return $this->repo->get($key);
        }
        $this->repo->set($key, $value);
        return $this;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->getPanel(), $method], $arguments);
    }
}
