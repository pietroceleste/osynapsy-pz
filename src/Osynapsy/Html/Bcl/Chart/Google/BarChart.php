<?php
namespace Osynapsy\Html\Bcl\Chart\Google;

class BarChart extends BaseChart
{
    public const ORIENTATION_HORIZONTAL = 'horizontal';
    public const ORIENTATION_VERTICAL = 'vertical';

    public function __construct(string $id, string $title = '')
    {
        parent::__construct($id, $title);
        $this->setType(self::TYPE_BAR);
        $this->setHeight(300)
             ->setLegend('bottom')
             ->setPadding();
    }

    public function setStacked(bool $stacked = true)
    {
        return $this->setOption('isStacked', $stacked);
    }

    public function setOrientation(string $orientation = 'vertical')
    {
        return $this->setOption('bars', $orientation); // Google Charts accetta 'vertical' o 'horizontal'
    }
}
