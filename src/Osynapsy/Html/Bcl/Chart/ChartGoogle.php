<?php
namespace Osynapsy\Html\Bcl\Chart;

use Osynapsy\Html\Ocl\ChartGoogle as OclChartGoogle;

/**
 * Description of ChartGoogle
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class ChartGoogle extends OclChartGoogle
{
    public function __construct($id, $title = 'No title', $type = 'BarChart')
    {
        parent::__construct($id, $title, $type);
        $this->requireJs('Bcl/Chart/Google/script.js');
    }
    
    public function __build_extra__()
    {        
        $this->addClass('bcl-chart-google');
        $this->att('data-type', $this->type);
        $this->att('data-columns', json_encode($this->columns, JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->att('data-options', json_encode($this->options, JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->att('data-rows',  json_encode($this->rows));
    }
    
    public function addRow($row)
    {
        $this->rows[] = $row;
    }
}
