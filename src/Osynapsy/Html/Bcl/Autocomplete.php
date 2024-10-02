<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Component;
use Osynapsy\Html\Tag;
use Osynapsy\Html\Ocl\HiddenBox as InputHidden;

class Autocomplete extends Component
{
    protected $emptyMessage = 'No value match <b>%s</b> query';
    protected $autocompleteclass = ['osy-autocomplete'];
    protected $ico = '<span class="fa fa-search"></span>';
    protected $decodeEntityIdFunction;
    protected $datasourceFunction;
    protected $hiddenField;
    protected $inputGroup;

    public function __construct($id)
    {
        parent::__construct('div', $id);
        $this->requireJs('Bcl/Autocomplete/script.js?v=1.02');
        $this->requireCss('Bcl/Autocomplete/style.css');
        $this->addClass('osy-autocomplete');
        $this->inputGroup = $this->inputGroupFactory();
    }

    protected function inputGroupFactory()
    {
        $Autocomplete = new InputGroup($this->id, '', $this->ico);
        $Autocomplete->getTextBox()->onselect = 'event.stopPropagation();';
        $Autocomplete->getTextBox()->onclick = 'event.stopPropagation();';
        return $Autocomplete;
    }

    public function __build_extra__()
    {
        if (filter_input(\INPUT_SERVER, 'HTTP_OSYNAPSY_HTML_COMPONENTS') != $this->id) {
            $hdnFieldId = '__'.$this->id;
            $this->add(new InputHidden($hdnFieldId));
            $this->add($this->inputMaskFactory($_REQUEST['__'.$this->id] ?? null));
            return;
        }
        $userQuery = filter_input(\INPUT_POST, $this->id);
        $dataset = $this->loadDataset($userQuery);
        $this->add($this->valueListFactory($dataset, $userQuery));
    }

    protected function inputMaskFactory($value)
    {
        if (!empty($this->decodeEntityIdFunction)) {
            $function = $this->decodeEntityIdFunction;
            $this->inputGroup->getTextBox()->setValue($function($value));
        }
        return $this->inputGroup;
    }

    protected function getDecodedValue()
    {
        list($decodeSql, $decodeSqlParams) = $this->query['decode'];
        return !empty($decodeSql) ? $this->db->findOne($decodeSql, $decodeSqlParams ?: []) : null;
    }

    protected function loadDataset($query)
    {
        $fnc = ($this->datasourceFunction);
        return $fnc($query);
    }

    protected function valueListFactory($dataset, $userQuery)
    {
        $valueList = new Tag('div', $this->id.'_list');
        if (empty($dataset) || !is_array($dataset)) {
            $valueList->add($this->emptyListMessageFactory($userQuery));
            return $valueList;
        }
        foreach ($dataset as $rec) {
            $val = array_values($rec);
            if (empty($val) || empty($val[0])) {
                continue;
            }
            switch (count($val)) {
                case 1:
                    $val[1] = $val[2] = $val[0];
                    break;
                case 2:
                    $val[2] = $val[1];
                    break;
            }

            $val[2] = str_replace($userQuery, '<b>'.$userQuery.'</b>', $val[2]);
            $valueList->add('<div class="item" data-value="'.$val[0].'" data-label="'.$val[1].'">'.$val[2].'</div>'.PHP_EOL);
        }
        return $valueList;
    }

    protected function emptyListMessageFactory($userQuery)
    {        
        return sprintf('<div class="item empty-message">'. $this->emptyMessage .'</div>',  $userQuery);
    }

    public function setLabel($label)
    {
        $_REQUEST[$this->id] = $label;
        return $this;
    }

    public function setEmptyMessage($msg)
    {
        $this->emptyMessage = $msg;
        return $this;
    }

    public function onSelect($function)
    {
        $this->onselect = $function;
        return $this;
    }

    public function onUnSelect($function)
    {
        $this->onunselect = $function;
        return $this;
    }

    public function setIco($ico)
    {
        $this->ico = $ico;
    }

    public function addAutocompleteClass($class)
    {
        $this->autocompleteclass[] = $class;
    }

    public function setDecodeEntityId(callable $decodeFunction)
    {
        $this->decodeEntityIdFunction = $decodeFunction;
    }

    public function setDatasource(callable $datasourceFunction)
    {
        $this->datasourceFunction = $datasourceFunction;
    }

    public function getInputGroup()
    {
        return $this->inputGroup;
    }
}
