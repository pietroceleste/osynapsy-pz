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
    public function __construct($id, $pageDimension = 10, $tag = 'div')
    {
        parent::__construct($tag, $id);        
        $this->requireJs('Bcl3/Pager/script.js');
        $this->addClass('BclPager');
        if ($tag == 'form') {
            $this->att('method','post');
        }
        $this->setPageDimension($pageDimension);
    }

    public function __build_extra__()
    {       
        $this->add(new HiddenBox($this->id));
        $this->add(new HiddenBox($this->id.'OrderBy'))->addClass('BclPaginationOrderBy');
        $ul = $this->add(new Tag('ul', null, 'pagination'));
        $ul->att('class','pagination');
        $ul->add($this->firstItemFactory($this->page['current']));
        $dim = min($this->boxLength, $this->page['total']);
        $app = floor($dim / 2);
        $pageMin = max(1, $this->page['current'] - $app);
        $pageMax = max($dim, min($this->page['current'] + $app, $this->page['total']));
        $pageMin = min($pageMin,$this->page['total'] - $dim + 1);
        for ($i = $pageMin; $i <= $pageMax; $i++) {
            $ul->add($this->pageItemFactory($this->page['current'], $i));
        }
        $ul->add($this->lastItemFactory($this->page['current'], $this->page['total']));
    }

    protected function firstItemFactory($currentPageIdx)
    {
        $li = new Tag('li');
        if ($currentPageIdx < 2) {
            $li->att('class','disabled');
        }
        $li->add(new Tag('a', null, 'page-item'))
                ->att('data-value','first')
                ->att('href','#')
                ->add('&laquo;');
        return $li;
    }

    protected function pageItemFactory($currentPageIdx, $itemIdx)
    {
        $li = new Tag('li', null, 'page-item');
        if ($itemIdx == $currentPageIdx) {
            $li->att('class','active', true);
        }
        $iGet = $this->paginationType != 'GET' ? '#' : ($itemIdx > 1 ? './'.$itemIdx : './');
        $li->addClass('text-center');
        $li->add(new Tag('a', null, 'page-link'))
           ->att('data-value', $itemIdx)
           ->att('href', $iGet)
           ->add($itemIdx);
        return $li;
    }

    protected function lastItemFactory($currentPageIdx, $lastPageIdx)
    {
        $liLast = new Tag('li');
        if ($currentPageIdx >= $lastPageIdx) {
            $liLast->att('class','disabled');
        }
        $liLast->add(new Tag('a', null, 'page-link'))
               ->att('href','#')
               ->att('data-value','last')
               ->add('&raquo;');
        return $liLast;
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
              ->setData($this->pageDimensions);
        return $Combo;
    }

    public function getSql()
    {
        return $this->sql;
    }
    
    public function loadData($requestPage = null)
    {
        if (empty($this->sql)) {
            return [];
        }
        if (is_null($requestPage) && filter_input(\INPUT_POST, $this->id)) {
            $requestPage = filter_input(\INPUT_POST, $this->id);
        }
        $rawQuery = $this->sql;
        $where = empty($this->filters) ? '' : $this->whereClauseFactory($this->filters);
        $this->total['rows'] = $this->countRecord($rawQuery,  $this->par, $where);
        $this->calcPage($requestPage);
        $this->sql = $this->pagingQueryFactory($this->sql, $where);
        try {
            $this->data = $this->db->execQuery($this->sql, $this->par, 'ASSOC');
            //die(print_r($this->data,true));            
            return empty($this->data) ? [] : $this->data;
        } catch (\Exception $e) {
            die($this->formatSqlErrorMessage($rawQuery, $e->getMessage()));
        }        
    }

    private function whereClauseFactory($filters)
    {        
        $filter = [];
        $i = 0;
        foreach ($filters as $field => $value) {
            if (is_null($value)) {
                $filter[] = $field;
                continue;
            }
            $filter[] = sprintf("%s = %s", $field, $this->db->getType() == 'oracle' ? ':'.$i : '?');
            $this->par[] = $value;
            $i++;
        }
        return sprintf(" WHERE %s", implode(' AND ',$filter));
    }

    protected function countRecord($sql, $sqlParameters, $where)
    {        
        $sqlQuery = sprintf("SELECT COUNT(*) FROM (%s) a %s", $sql, $where);
        try {
            return $this->db->execUnique($sqlQuery, $sqlParameters);
        } catch(\Exception $e) {
            $this->errors[] = sprintf('<pre>%s\n%s</pre>', $sqlQuery, $e->getMessage());
        }
        return 0;
    }

    protected function pagingQueryFactory($sql, $where)
    {
        $orderByClause = $this->orderByClauseFactory();
        switch ($this->db->getType()) {
            case 'oracle':
                return $this->pagingQueryOracleFactory($sql, $where, $orderByClause);
            case 'pgsql':
                return $this->pagingQueryPgSqlFactory($sql, $where, $orderByClause);
            default:
                return $this->pagingQueryMySqlFactory($sql, $where, $orderByClause);
        }
    }

    private function pagingQueryMySqlFactory($query, $where, $orderBy)
    {
        $sql = sprintf("SELECT a.* FROM (%s) a %s %s", $query, $where, $orderBy);
        if (empty($this->page['dimension'])) {
            return $sql;
        }
        $startFrom = max(0, ($this->page['current'] - 1) * $this->page['dimension']);
        $sql .= sprintf("\nLIMIT %s, %s", $startFrom, $this->page['dimension']);
        return $sql;
    }

    private function pagingQueryPgSqlFactory($rawQuery, $where, $orderBy)
    {
        $sql = sprintf("SELECT a.* FROM (%s) a %s %s", $rawQuery, $where, $orderBy);
        if (!empty($this->page['dimension'])) {
            $startFrom = max(0, ($this->page['current'] - 1) * $this->page['dimension']);
            $sql .= sprintf("\nLIMIT %s OFFSET %s", $this->page['dimension'], $startFrom);
        }        
        return $sql;
    }

    private function pagingQueryOracleFactory($rawQuery, $where, $orderBy)
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
            $rawQuery,
            $where,
            $orderBy
        );
        if (!empty($this->page['dimension'])) {
            $startFrom = (($this->page['current'] - 1) * $this->page['dimension']) + 1 ;
            $endTo = ($this->page['current'] * $this->page['dimension']);
            $sql .=  sprintf('WHERE "_rnum" BETWEEN %s AND %s', $startFrom, $endTo);
        }        
        return $sql;
    }

    protected function orderByClauseFactory()
    {
        if (!empty($_REQUEST[$this->id.'OrderBy'])) {
            $this->setOrder($_REQUEST[$this->id.'OrderBy']);
        }         
        return !empty($this->orderBy) ? sprintf('ORDER BY %s', $this->orderBy) : '1 DESC';
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
