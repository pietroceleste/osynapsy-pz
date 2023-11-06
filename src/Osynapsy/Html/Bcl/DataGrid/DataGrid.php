<?php
namespace Osynapsy\Html\Bcl\DataGrid;

use Osynapsy\Html\Component;
use Osynapsy\Html\Tag;
use Osynapsy\Db\Paging\Paging;

class DataGrid extends Component
{
    private $columns = [];
    private $title;    
    private $footer = [];
    private $thClass = 'bcl-datagrid-th bcl-datagrid-th-order-by';
    private $emptyMessage = 'No data found';
    private $showHead = true;
    protected $pager;

    public function __construct($name, $debugQuery = false)
    {
        parent::__construct('div', $name);
        $this->addClass('bcl-datagrid');
        $this->requireCss('Bcl3/DataGrid/style.css');
        $this->requireJs('Bcl3/DataGrid/script.js');
        $this->debug = $debugQuery;
    }

    public function __build_extra__()
    {
        $this->count++;            
        //If datagrid has pager get data from it.        
        if (!empty($this->pager)) {
            try {
                $this->setData($this->pager->paging->getDataset());                
            } catch (\Exception $e) {
                $this->printError($e->getMessage());
            }
        }
        if (!empty($this->title)) {
            $this->add($this->buildTitle($this->title));
        }
        if (!empty($this->showHead)){
            $this->add($this->theadFactory());
        }
        $this->add($this->tbodyFactory());
        if ($this->debug) {
            $this->footer = [sprintf('<pre style="margin: 5px 0px; border:1px solid #ddd;">%s</pre>', $this->pager->paging->getMeta(Paging::META_PAGING_QUERY))];
        }
        if (!empty($this->footer)) {
            $this->add($this->buildFooter($this->footer));
        }
        //If datagrid has pager append to foot and show it.
        if (!empty($this->pager)) {
            $this->add($this->paginationFactory($this->pager));
        }
    }

    private function buildTitle($title)
    {
        $tr = new Tag('div', null, 'row bcl-datagrid-head');
        $tr->add(new Tag('div', null, 'col-lg-12'))->add($title);
        return $tr;
    }

    private function theadFactory()
    {
        $thead = new Tag('div', null, 'row bcl-datagrid-thead');
        $orderByFields = $this->pager ? explode(',', $this->pager->paging->getOrderBy()) : null;
        foreach($this->columns as $label => $properties) {            
            if (empty($properties['hideTh'])) {
                $thead->add($this->thFactory($label, $properties, $orderByFields));
            }            
        }
        return $thead;
    }

    public function thFactory($label, $properties, $orderedFields)
    {
        $orderByField = $properties['orderByField'];
        $keyClass = empty($properties['classTh']) ? 'class' : 'classTh';
        $th = new Tag('div', null, $this->thClass . ' ' . str_replace('d-block', '', $properties[$keyClass]));
        $th->att('data-idx', $orderByField)->add($label);
        if (empty($orderedFields)) {
            return $th;
        }
        foreach ([$orderByField, $orderByField.' DESC'] as $i => $token) {
            $key = array_search($token, $orderedFields);
            if ($key === false) {
                continue;
            }
            $icon = ($key + 1).' <i class="fa fa-arrow-'.(empty($i) ? 'up' : 'down').'"></i>';
            $th->add('<span class="bcl-datagrid-th-order-label">'.$icon.' </span>');
        }
        return $th;
    }

    private function tbodyFactory()
    {
        $body = new Tag('div', null, 'bcl-datagrid-body');
        if (empty($this->data)) {
            $body->add($this->emptyMessageFactory($this->emptyMessage));
            return $body;
        }
        foreach ($this->data as $rec) {
            $body->add($this->buildRow($rec));
        }
        return $body;
    }

    /**
     * Build Datagrid pagination
     *
     * @return Tag
     */
    private function paginationFactory($pagination)
    {
        $row = new Tag('div', null, 'row bcl-datagrid-pagination');
        if (empty($pagination)) {
            return $row;
        }
        $row->add(new Tag('div', null, 'col-lg-4 col-xs-4 col-4'))
            ->add($this->pager->getPageDimensionsCombo());
        $row->add(new Tag('div', null, 'col-lg-4 col-xs-4 col-4 text-center'))
             ->add('<label class="" style="margin-top: 30px;">'.$pagination->getInfo().'</label>');
        $row->add(new Tag('div', null, 'col-lg-4 col-xs-4 col-4 text-right'))
             ->add($pagination)
             ->setClass('mt-4');
        return $row;
    }

    private function emptyMessageFactory($emptyMessage)
    {        
        return '<div class="row"><div class="col-lg-12 text-center">'.$emptyMessage.'</div></div>';
    }

    private function buildRow($row)
    {
        $tr = new Tag('div', null, 'row');
        foreach ($this->columns as $properties) {
            $value = array_key_exists($properties['field'], $row) ?
                     $row[$properties['field']] :
                     '<label class="label label-warning">No data found</label>';
            $cell = $tr->add(new Tag('div', null, 'bcl-datagrid-td'));
            $cell->add(
                $this->valueFormatting($value, $cell, $properties, $row, $tr)
            );
        }
        return $tr;
    }

    private function buildFooter(array $footer)
    {
        $foot = new Tag('div', null, 'bcl-datagrid-footer');
        foreach ($footer as $content) {
            $foot->add(new Tag('div', null, 'row'))->add($content);
        }
        return $foot;
    }

    private function valueFormatting($value, &$cell, $properties, &$rec, $tr)
    {
        switch($properties['type']) {
            case 'check':
                $value = sprintf('<input type="checkbox" name="%s_chk[]" value="%s">', $this->id, $value);
                $properties['class'] .= ' text-center';
                break;
            case 'money-right':
                $properties['class'] .= ' text-right';
            case 'money':
                $value = number_format($value, 2, ',', '.'). ' &euro;';
                break;
            case 'integer':
                $value = number_format($value, 0, ',', '.');
                $properties['class'] .= ' text-right';
                break;
            case 'actionTextBox':
                $value = '<input type="text" name="'.$this->id.ucfirst($properties['field']).'[]" value="'.$value.'" class="form-control input-xs change-execute" data-action="'.$this->id.''.ucfirst($properties['field']).'Change" data-action-parameter="this.value">';
                break;
        }
        if (!empty($properties['function'])) {
            $value = $properties['function']($value, $rec, $cell, $tr);
        }
        if (!empty($properties['prefix'])) {
            $value = $properties['prefix'].' '.$value;
        }
        if (!empty($properties['class'])) {
            $cell->att('class', $properties['class'], true);
        }
        return $value;
    }

    public function addColumn($label, $field, $class = '', $type = 'string', callable $function = null, $prefix = null)
    {
        $this->columns[$label] = [
            'field' => $field,
            'class' => $class,
            'type' => $type,
            'prefix' => $prefix,
            'function' => $function,
            'orderByField' => $field
        ];
        return $this;
    }

    private function printError($error)
    {
        $this->setData([['error' => str_replace(PHP_EOL,'<br>',$error)]]);
        $this->columns = [];
        $this->addColumn('Error', 'error', 'col-lg-12');
    }

    public function setColumnProperty($columnId, $property, $value)
    {
        $this->columns[$columnId][$property] = $value;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setFooter(array $footer)
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * Set a pagination object
     *      *
     * @param type $db Handler db connection
     * @param string $sqlQuery Sql query
     * @param array $sqlParameters Parameters of sql query
     * @param integer $pageDimension Page dimension (in row)
     */
    public function setPagination($dbCn, $query, $queryParameters, $pageDimension = 10, $orderBy = null)
    {
        $dbPager = new Paging($dbCn, $query, $queryParameters, $pageDimension);
        $dbPager->setOrderBy($orderBy ?: 1);
        $pagerId = $this->id . (strpos($this->id, '_') ? '_pagination' : 'Pagination');        
        $pager = new Pager($pagerId, $dbPager);
        $pager->addClass('refreshParent');        
        $pager->setParentComponent($this->id);              
        return $this->pager = $pager;
    }

    public function setEmptyMessage($message)
    {
        $this->emptyMessage = $message;
        return $this;
    }

    public function showHead($value)
    {
        $this->showHead = $value;
    }

    public function hideHeadOnMobile()
    {
        $this->thClass .= ' hidden-xs d-none d-sm-block';
    }
}