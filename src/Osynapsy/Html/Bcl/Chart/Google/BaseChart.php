<?php
namespace Osynapsy\Html\Bcl\Chart\Google;

use Osynapsy\Html\Component;

/**
 * BaseChart is the abstract foundation for all Google Charts components.
 *
 * This class defines the common interface and core logic for rendering
 * Google Charts within the Osynapsy framework. It manages chart data,
 * options, titles, and HTML container generation. Specific chart types
 * such as BarChart, ColumnChart, LineChart, and PieChart should extend
 * this class and define their own visualization type.
 *
 * Responsibilities:
 * - Define a standard chart data structure.
 * - Handle JSON encoding for Google Chart compatibility.
 * - Manage chart options such as title, legend, colors, and dimensions.
 * - Render the HTML and JavaScript required to initialize the chart.
 *
 * Example usage (in derived classes):
 * ```php
 * $chart = new ColumnChart('chart1', 'Monthly Sales');
 * $chart->setData([
 *     ['Month', 'Sales'],
 *     ['January', 1000],
 *     ['February', 1170],
 * ]);
 * echo $chart;
 * ```
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 * @package Osynapsy\Html\Bcl\Chart\Google
 */
class BaseChart extends Component
{
    public const TYPE_PIE = 'PieChart';
    public const TYPE_BAR = 'BarChart';
    public const TYPE_COLUMN = 'ColumnChart';
    public const TYPE_LINE = 'LineChart';
    public const TYPE_AREA = 'AreaChart';

    public const LEGEND_POSITION_TOP = 'top';
    public const LEGEND_POSITION_BOTTOM = 'bottom';
    
    public const THEMES = [
        'blueYellow' => ['#006BFF', '#FFD301'],
        'greenRed'   => ['#2ECC71', '#E74C3C'],
        'grayScale'  => ['#E0E0E0', '#333333'],
        'tealPurple' => ['#00BFA5', '#9C27B0'],
        'navyCyan'   => ['#001F54', '#00B4D8'],
    ];

    protected $columns = [];
    protected $type;
    protected $options = ['title' => 'No title'];

    public function __construct($id, $title = 'No title', $type = 'BarChart')
    {
        parent::__construct('div', $id);
        $this->setType($type);
        $this->setOption('title', $title);
        $this->requireJs('//www.gstatic.com/charts/loader.js');
        $this->requireJs('Bcl/Chart/Google/script.js');
    }

    public function __build_extra__()
    {
        $this->addClass('bcl-chart-google');
        $this->att('data-type', $this->type);
        $this->att('data-columns', json_encode($this->columns, JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->att('data-options', json_encode($this->options, JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->att('data-rows',  json_encode($this->data));
    }

    public function addColumn($name, $type = 'string')
    {
        $this->columns[$name] = $type;
        return $this;
    }

    public function addRow(array $row)
    {
        $this->data[] = $row;
        return $this;
    }

    public function applyGradientPalette(string $themeOrColorStart, ?string $colorEnd = null, int $numSlices = 25): self
    {
        // Se il primo parametro è un tema, sostituisci i colori
        if (isset(self::THEMES[$themeOrColorStart])) {
            [$colorStart, $colorEnd] = self::THEMES[$themeOrColorStart];
        } else {
            $colorStart = $themeOrColorStart;
            $colorEnd = $colorEnd ?? '#FFD301';
        }
            // Converte colore hex in RGB
        $hexToRgb = fn($hex) => [
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2))
        ];

        $startRgb = $hexToRgb($colorStart);
        $endRgb   = $hexToRgb($colorEnd);
        $colors   = [];

        /*for ($i = 0; $i < $numSlices; $i++) {
            // Applichiamo una curva "ease-in-out" (più lenta all’inizio e alla fine)
            $t = $i / ($numSlices - 1);
            $t = $t * $t * (3 - 2 * $t); // curva smoothstep

            $r = (int) round($startRgb[0] + ($endRgb[0] - $startRgb[0]) * $t);
            $g = (int) round($startRgb[1] + ($endRgb[1] - $startRgb[1]) * $t);
            $b = (int) round($startRgb[2] + ($endRgb[2] - $startRgb[2]) * $t);

            $colors[] = sprintf("#%02X%02X%02X", $r, $g, $b);
        }*/

        for ($i = 0; $i < $numSlices; $i++) {
            $r = (int)($startRgb[0] + ($endRgb[0] - $startRgb[0]) * $i / max($numSlices - 1, 1));
            $g = (int)($startRgb[1] + ($endRgb[1] - $startRgb[1]) * $i / max($numSlices - 1, 1));
            $b = (int)($startRgb[2] + ($endRgb[2] - $startRgb[2]) * $i / max($numSlices - 1, 1));
            $colors[] = sprintf("#%02X%02X%02X", $r, $g, $b);
        }
        
        return $this->setColors($colors);
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setColors(array $colors)
    {
        return $this->setOption('colors', $colors);
    }

    public function setHeight(int $height)
    {
        return $this->setOption('height', $height);
    }

    public function setLegend(string $position = 'bottom', string $alignment = 'center', $fontSize = 12, $textColor = '#333')
    {
        return $this->setOption('legend', [
            'position' => $position,
            'alignment' => $alignment,
            'textStyle' => [
                'fontSize' => $fontSize,
                'color' => $textColor
            ]
        ]);
    }

    public function setType($type)
    {
        $this->type = $type;
        $this->att('data-type', $type);
        return $this;
    }

    public function setPadding(int $top = 30, int $right = 20, int $bottom = 30, int $left = 70)
    {
        return $this->setOption('chartArea', [
            'top' => $top,
            'right' => $right,
            'bottom' => $bottom,
            'left' => $left,
            'width' => '90%',
            'height' => '70%',
        ]);
    }

    public function getConfig(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'options' => $this->options,
        ];
    }
}
