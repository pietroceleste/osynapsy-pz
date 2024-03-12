<?php
namespace Osynapsy\Html\Bcl\DataGrid;

use Osynapsy\Html\Component;
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Html\Bcl\ComboBox;
use Osynapsy\Html\Tag;
use Osynapsy\Db\Paging\Paging;

/**
 * Description of Pager
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Pager extends Component
{    
    protected $entity = 'Record';
    public $errors = [];        
    protected $filters = [];
    protected $loaded = false;
    protected $orderBy = null;    
    protected $pageDimensionPalceholder = '- Dimensione pagina -';
    protected $parentComponent;    
    protected $boxLength = 7;
    public $paging;
    public $paginationType = 'POST';
    public $pageDimensions = [];

    public function __construct($id, Paging $paging)
    {        
        parent::__construct('div', $id);        
        $this->requireJs('Bcl3/Pager/script.js');
        $this->addClass('BclPager');
        $this->paging = $paging;
        if (!empty($_REQUEST[$this->id])) {
            $this->paging->setRequestPage($_REQUEST[$this->id]);
        }
        if (!empty($_REQUEST[$this->id.'OrderBy'])) {
            $this->paging->setOrderBy($_REQUEST[$this->id.'OrderBy']);
        }
        if (!empty($_REQUEST[$this->id.'PageDimension'])) {
            $this->paging->setRequestPageDimension($_REQUEST[$this->id.'PageDimension']);
        } elseif (!empty($_REQUEST[$this->id.'_page_dimension'])) {
            $this->paging->setRequestPageDimension($_REQUEST[$this->id.'_page_dimension']);
        }
    }

    public function __build_extra__()
    {        
        $pageCurrent = $this->getmeta('pageCurrent');
        $pageTotal = $this->getMeta('pageTotal');
        $this->add(new HiddenBox($this->id));
        $this->add(new HiddenBox($this->id.'OrderBy'))->addClass('BclPaginationOrderBy');        
        $ul = $this->add(new Tag('ul', null, 'pagination'));
        $ul->att('class','pagination');
        $ul->add($this->firstItemFactory($pageCurrent));
        $dim = min($this->boxLength, $pageTotal);
        $app = floor($dim / 2);
        $pageMin = max(1, $pageCurrent - $app);
        $pageMax = max($dim, min($pageCurrent + $app, $pageTotal));
        $pageMin = min($pageMin, $pageTotal - $dim + 1);
        for ($i = $pageMin; $i <= $pageMax; $i++) {
            $ul->add($this->pageItemFactory($pageCurrent, $i));
        }
        $ul->add($this->lastItemFactory($pageCurrent, $pageTotal));
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

    public function getInfo()
    {
        $pageCurrent = $this->getMeta('pageCurrent');
        $pageDimension = $this->getMeta('pageDimension');
        $numberOfRows = $this->getMeta('numberOfRows');
        if (empty($numberOfRows)) {
            return false;
        }
        $end = min($pageCurrent * $pageDimension, $numberOfRows);
        $start = ($pageCurrent - 1) * $pageDimension + 1;        
        return sprintf('da %s a %s di %s %s', $start, $end, $numberOfRows, $this->entity);
    }    

    public function comboPageDimensionFactory()
    {
        $pageDimension = $this->paging->getMeta(Paging::META_PAGE_DIMENSION);
        $pageDimensions = $this->getPageDimensionComboOptions($pageDimension);
        $Combo = new ComboBox($this->id.(strpos($this->id, '_') ? '_page_dimension' : 'PageDimension'));
        $Combo->setPlaceholder($this->pageDimensionPalceholder);
        $Combo->att('onchange',"FormController.refreshComponent(['#{$this->parentComponent}'])")
              ->att('style','margin-top: 20px;')
              ->setData($pageDimensions);
        return $Combo;
    }

    public function getPageDimensionComboOptions($pageDimension)
    {                
        $pageDimensions = [];
        foreach([1, 2, 5, 10, 50] as $key) {
            $dimension = $pageDimension * $key;
            $pageDimensions[$key] = [$dimension, "{$dimension} righe"];
        }
        return $pageDimensions;
    }

    public function setParentComponent($componentId)
    {
        $this->parentComponent = $componentId;
        $this->att('data-parent', $componentId);
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

    public function getMeta($key = null)
    {
        return $this->paging->getmeta($key);
    }
}
