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
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Html\Bcl\Panel;

class Tab extends Component
{
    private $ul;
    private $nCard = 0;
    private $currentCard;
    private $tabContainer;
    private $id;
    
    public function __construct($id)
    {
        parent::__construct('dummy');        
        $this->requireJs('Bcl/Tab/script.js');
        $this->id = $id;
        $this->ul = $this->add($this->navTabsFactory($id));
        $this->ul->add(new HiddenBox($id));
        $this->tabContainer = $this->add(new Tag('div', null, 'tab-content'));
    }

    protected function navTabsFactory($id)
    {
        $ul = new Tag('ul', $id.'_nav', 'nav nav-tabs');
        $ul->att(['role' => 'tablist', 'data-tabs' => 'tabs']);
        return $ul;
    }
    
    public function addCard($title, $panelClass = Panel::class)
    {
        $cardId = sprintf('%s_%s', $this->id, $this->nCard++);
        $this->ul->add($this->cardTabFactory($cardId, $title));
        $this->currentCard = $this->tabContainer->add($this->cardPanelFactory($cardId, $panelClass));
        return $this->currentCard;
    }

    protected function cardTabFactory($cardId, $title)
    {
        $li = new Tag('li');
        $li->att('role','presentation');
        $li->add(sprintf('<a href="#%s" data-toggle="tab">%s</a>', $cardId, $title));
        return $li;
    }

    protected function cardPanelFactory($cardId, $panelClass)
    {
        $Panel = new $panelClass($cardId);
        $Panel->addClass('tab-pane fade no-border');
        return $Panel;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->currentCard, $name], $arguments);
    }
}
