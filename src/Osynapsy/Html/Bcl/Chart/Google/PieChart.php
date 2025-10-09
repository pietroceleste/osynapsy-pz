<?php
namespace Osynapsy\Html\Bcl\Chart\Google;

/**
 * Description of PieChart
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class PieChart extends BaseChart
{
    public function __construct(string $id, string $title = '')
    {
        parent::__construct($id, $title);
        $this->setType(self::TYPE_PIE);
        $this->setHeight(250)
             ->setDonut()
             ->setLegend('bottom')
             ->setPadding();
    }

    public function setDonut(float $hole = 0.4)
    {
        return $this->setOption('pieHole', $hole);
    }
}
