<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;

class ContextMenu extends Component
{
    protected $items;

    public function __construct($id)
    {
        $this->requireCss('Bcl/ContextMenu/style.css');
        $this->requireJs('Bcl/ContextMenu/script.js');
        parent::__construct('div', $id);
        $this->att('class', 'BclContextMenu dropdown clearfix');
    }

    public function __build_extra__(): void
    {
        $ul = $this->add(new Tag('ul', null, 'dropdown-menu'));
        $ul->att([
            'role' => 'menu',
            'aria-labelledby' => 'dropdownMenu',
            'style' => 'display: block; position: static; margin-bottom: 5px;'
        ]);
        foreach($this->items as $item) {
            $ul->add(new Tag('li'))->add($item);
        }
    }


    public function addItem($label, $action, array $params = [])
    {
        $item = new Link(false, false, $label);
        $item->att('data-action', $action);
        if (!empty($params)) {
             $item->att('data-action-param', implode(",", $params));
        }
        $this->items[] = $item;
    }
}
