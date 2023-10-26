<?php
namespace Osynapsy\Html\Bcl\DataGrid;

use Osynapsy\Html\Component;
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Html\Bcl\ComboBox;
use Osynapsy\Html\Tag;
/**
 * Description of Pager
 *
 * @author pietr
 */
class Pager extends Component
{
    protected $columns = array();
    protected $data = array();
    protected $entity = 'Record';
    public $errors = [];
    protected $db;
    protected $filters = array();
    protected $loaded = false;
    protected $par;
    protected $sql;
    protected $orderBy = null;
    protected $page = ['dimension' => 10, 'total' => 1, 'current' => 1];
    protected $pageDimensionPalceholder = '- Dimensione pagina -';
    protected $parentComponent;
    protected $total = ['rows' => 0];
    protected $boxLength = 7;
    public $paginationType = 'POST';
    public $pageDimensions = [
        1 => ['10', '10 righe'],
        2 => ['20', '20 righe'],
        5 => ['50', '50 righe'],
        10 => ['100', '100 righe'],
        20 => ['200', '200 righe']
    ];

    //put your code here
    public function __construct($id, $pageDimension = 10, $tag = 'div', $infiniteContainer = false)
    {
        parent::__construct($tag, $id);
        if (!empty($infiniteContainer)) {
            $this->setInfiniteScroll($infiniteContainer);
        }
        $this->requireJs('Bcl3/Pager/script.js');
        $this->att('class','BclPager',true);
        if ($tag == 'form') {
            $this->att('method','post');
        }
        $this->setPageDimension($pageDimension);
    }

    public function __build_extra__()
    {
        if (!$this->loaded) {
            $this->loadData;
        }
        $this->add(new HiddenBox($this->id));
        $this->add(new HiddenBox($this->id.'OrderBy'))->addClass('BclPaginationOrderBy');
        $ul = $this->add(new Tag('ul', null, 'pagination'));
        $ul->att('class','pagination');
        $liFirst = $ul->add(new Tag('li'));
        if ($this->page['current'] < 2) {
            $liFirst->att('class','disabled');
        }
        $liFirst->add(new Tag('a', null, 'page-link'))
                ->att('data-value','first')
                ->att('href','#')
                ->add('&laquo;');
        $dim = min($this->boxLength, $this->page['total']);
        $app = floor($dim / 2);
        $pageMin = max(1, $this->page['current'] - $app);
        $pageMax = max($dim, min($this->page['current'] + $app, $this->page['total']));
        $pageMin = min($pageMin,$this->page['total'] - $dim + 1);
        for ($i = $pageMin; $i <= $pageMax; $i++) {
            $liCurrent = $ul->add(new Tag('li', null, 'page-item'));
            if ($i == $this->page['current']) {
                $liCurrent->att('class','active', true);
            }
            $iGet = '#';
            if ($this->paginationType == 'GET') {
                $iGet = ($i > 1) ? './'.$i : './';
            }
            $liCurrent->att('class','text-center',true)
                      ->add(new Tag('a', null, 'page-link'))
                      ->att('data-value',$i)
                      ->att('href', $iGet)
                      ->add($i);
        }
        $liLast = $ul->add(new Tag('li'));
        if ($this->page['current'] >= $this->page['total']) {
            $liLast->att('class','disabled');
        }
        $liLast->add(new Tag('a', null, 'page-link'))
               ->att('href','#')
               ->att('data-value','last')
               ->add('&raquo;');
    }

    public function addFilter($field, $value = null)
    {
        $this->filters[$field] = $value;
    }
       
    private function calcPage($requestPage)
    {

        $this->page['current'] = max(1, (empty($requestPage) ? 0 :(int) $requestPage));
        if ($this->total['rows'] == 0 || empty($this->page['dimension'])) {
            return;
        }
        $this->page['total'] = ceil($this->total['rows'] / $this->page['dimension']);
        $this->att('data-page-max', max($this->page['total'],1));
        switch ($requestPage) {
            case 'first':
                $this->page['current'] = 1;
                break;
            case 'last' :
                $this->page['current'] = $this->page['total'];
                break;
            case 'min':
                if ($this->page['current'] > 1){
                    $this->page['current']--;
                }
                break;
            case 'min':
                if ($this->page['current'] < $this->page['total']) {
                    $this->page['current']++;
                }
                break;
            default:
                $this->page['current'] = min($this->page['current'], $this->page['total']);
                break;
        }
    }

    private function calcStatistics()
    {
        //Calcolo statistiche
        if (!$this->sqlStat) {
            return;
        }
        try {
            $sql_stat = Kernel::replaceVariable(str_replace('<[datasource-sql]>',$sql,$sql_stat).$whr);
            $stat = $this->db->execUnique($sql_stat,null,'ASSOC');
            if (!is_array($stat)) $stat = array($stat);
            $dstat = tag::create('div')->att('class',"osy-datagrid-stat");
            $tr = $dstat->add(tag::create('table'))->att('align','right')->add(tag::create('tr'));
            foreach ($stat as $k=>$v) {
                $v = ($v > 1000) ? number_format($v,2,',','.') : $v;
                $tr->add(Tag::create('td'))->add('&nbsp;');
                $tr->add(Tag::create('td'))->att('title',$k)->add($k);
                $tr->add(Tag::create('td'))->add($v);
            }
            $this->__par['div-stat'] = $dstat;
        } catch(\Exception $e) {
                $this->setParameter('error-in-sql-stat','<pre>'.$sql_stat."\n".$e->getMessage().'</pre>');
        }
    }

    public function getInfo()
    {
        $end = min($this->page['current'] * $this->page['dimension'], $this->total['rows']);
        $start = ($this->page['current'] - 1) * $this->page['dimension'] + 1;
        $info = 'da ';
        $info .= $start;
        $info .= ' a ';
        $info .= $end;
        $info .= ' di ';
        $info .= $this->total['rows'];
        $info .= ' ';
        $info .= $this->entity;

        return $info;
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    public function getPageDimensionsCombo()
    {
        $Combo = new ComboBox($this->id.(strpos($this->id, '_') ? '_page_dimension' : 'PageDimension'));
        $Combo->setPlaceholder($this->pageDimensionPalceholder);
        $Combo->att('onchange',"FormController.refreshComponent(['#{$this->parentComponent}'])")
              ->att('style','margin-top: 20px;')
              ->setArray($this->pageDimensions);
        return $Combo;
    }

    public function loadData($requestPage = null)
    {
        if (empty($this->sql)) {
            return array();
        }
        if (is_null($requestPage) && filter_input(\INPUT_POST, $this->id)) {
            $requestPage = filter_input(\INPUT_POST, $this->id);
        }
        $where = $this->buildFilter();
        $count = "SELECT COUNT(*) FROM (\n{$this->sql}\n) a " . $where;
        $orderByClause = $this->orderByClauseFactory();
        try {
            $this->total['rows'] = $this->db->execUnique($count, $this->par);
            $this->att('data-total-rows',$this->total['rows']);
        } catch(\Exception $e) {
            $this->errors[] = '<pre>'.$count."\n".$e->getMessage().'</pre>';            
        }
        $this->calcPage($requestPage);
        switch ($this->db->getType()) {
            case 'oracle':
                $sql = $this->buildOracleQuery($where, $orderByClause);
                break;
            case 'pgsql':
                $sql = $this->buildPgSqlQuery($where);
                break;
            default:
                $sql = $this->buildMySqlQuery($where);
                break;
        }
        //echo "<pre>$sql</pre>{$this->orderBy}";
        //Eseguo la query
        try {
            $this->data = $this->db->execQuery($sql, $this->par, 'ASSOC');
        } catch (\Exception $e) {
            die($this->formatSqlErrorMessage($sql, $e->getMessage()));
        }
        //die(print_r($this->data,true));
        //Salvo le colonne in un option
        $this->columns = $this->db->getColumns();        
        return empty($this->data) ? [] : $this->data;
    }

    private function buildMySqlQuery($where, $orderBy)
    {
        $sql = sprintf("SELECT a.* FROM (%s) a %s %s", $this->sql, $where, $orderBy);
        if (empty($this->page['dimension'])) {
            return $sql;
        }
        $startFrom = max(0, ($this->page['current'] - 1) * $this->page['dimension']);
        $sql .= sprintf("\nLIMIT %s, %s", $startFrom, $this->page['dimension']);
        return $sql;
    }

    private function buildPgSqlQuery($where, $orderBy)
    {
        $sql = sprintf("SELECT a.* FROM (%s) a %s %s", $this->sql, $where, $orderBy);
        if (!empty($this->page['dimension'])) {
            $startFrom = max(0, ($this->page['current'] - 1) * $this->page['dimension']);
            $sql .= sprintf("\nLIMIT %s OFFSET %s", $this->page['dimension'], $startFrom);
        }        
        return $sql;
    }

    private function buildOracleQuery($where, $orderBy)
    {        
        $sql = sprintf(
            'SELECT a.*
                FROM (
                    SELECT b.*,rownum as "_rnum"
                    FROM (
                        SELECT a.* FROM (%s) a
                        %s
                        %s
                    ) b
                ) a ',
            $this->sql,
            $where,
            strtoupper($orderBy)
        );
        if (empty($this->page['dimension'])) {
            return $sql;
        }
        $startFrom = (($this->page['current'] - 1) * $this->page['dimension']) + 1 ;
        $endTo = ($this->page['current'] * $this->page['dimension']);
        $sql .=  "WHERE \"_rnum\" BETWEEN $startFrom AND $endTo";
        return $sql;
    }

    protected function orderByClauseFactory()
    {
        if (!empty($_REQUEST[$this->id.'OrderBy'])) {
            $this->setOrder($_REQUEST[$this->id.'OrderBy']);
        }         
        return !empty($this->orderBy) ? sprintf('ORDER BY %s', $this->orderBy) : '';
    }

    private function buildFilter()
    {
        if (empty($this->filters)) {
            return;
        }
        $filter = array();
        $i = 0;
        foreach ($this->filters as $field => $value) {
            if (is_null($value)) {
                $filter[] = $field;
                continue;
            }
            $filter[] = "$field = ".($this->db->getType() == 'oracle' ? ':'.$i : '?');
            $this->par[] = $value;
            $i++;
        }
        return " WHERE " .implode(' AND ',$filter);
    }

    public function setSql($db, $cmd, array $par = array())
    {
        $this->db = $db;
        $this->sql = $cmd;
        $this->par = $par;
        return $this;
    }

    public function setPageDimension($pageDimension)
    {
        if (!empty($_REQUEST[$this->id.'PageDimension'])) {
            $this->page['dimension'] = $_REQUEST[$this->id.'PageDimension'];
        } elseif (!empty($_REQUEST[$this->id.'_page_dimension'])) {
            $this->page['dimension'] = $_REQUEST[$this->id.'_page_dimension'];
        } else {
            $this->page['dimension'] = $pageDimension;
        }
        if ($pageDimension === 10) {
            return;
        }
        foreach(array_keys($this->pageDimensions) as $key) {
            $dimension = $pageDimension * $key;
            $this->pageDimensions[$key] = [$dimension, "{$dimension} righe"];
        }
    }

    public function setParentComponent($componentId)
    {
        $this->parentComponent = $componentId;
        $this->att('data-parent', $componentId);
        return $this;
    }

    public function setOrder($field)
    {
        $this->orderBy = str_replace(['][', '[', ']'], [',' ,'' ,''], $field);
        return $this;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    public function setInfiniteScroll($container)
    {
        $this->requireJs('/vendor/osynapsy/Bcl/Pager/imagesloaded.js');
        $this->requireJs('/vendor/osynapsy/Bcl/Pager/wookmark.js');
        $this->paginationType = 'GET';
        $this->att('class','infinitescroll',true)->att('style','display: none');
        if ($container[0] != '#' ||  $container[0] != '#') {
            $container = '#'.$container;
        }
        return $this->att('data-container',$container);
    }

    public function setRefreshComponent($componentId)
    {
        $this->att('action', 'refreshComponent');
        $this->att('data-target', $componentId);
    }

    public function setBoxLength($length)
    {
        $this->boxLength = $length;
        return $this;
    }

    private function formatSqlErrorMessage($sql, $rawerror)
    {
        $error = str_replace($sql, '', $rawerror);
        return sprintf('Query error :<pre style="background-color: #fefefe; border: 1px solid #ddd; padding: 5px;">%s</pre>Error message: <div>%s</div>', $sql, $error);
    }
}
