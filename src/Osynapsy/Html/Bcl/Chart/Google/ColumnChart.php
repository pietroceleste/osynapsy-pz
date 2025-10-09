<?php
namespace Osynapsy\Html\Bcl\Chart\Google;

/**
 * ColumnChart component for rendering vertical bar charts using Google Charts.
 *
 * This class extends the BarChart component but configures it to display
 * columns (vertical bars) instead of horizontal bars. It inherits all
 * features and configuration options from the BarChart base class,
 * including dataset management, legends, and chart options.
 *
 * Typical usage example:
 *
 * ```php
 * $chart = new ColumnChart('salesChart', 'Monthly Sales');
 * $chart->setData([
 *     ['Month', 'Sales'],
 *     ['January', 1000],
 *     ['February', 1170],
 *     ['March', 660],
 *     ['April', 1030],
 * ]);
 * ```
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 * @package Osynapsy\Html\Bcl\Chart\Google
 */
class ColumnChart extends BarChart
{
    public function __construct(string $id, string $title = '')
    {
        parent::__construct($id, $title);
        $this->setType(self::TYPE_COLUMN);
    }
}
