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

class InputGroup extends Component
{
    protected $textBox;
    protected $postfix;
    protected $prefix;

    public function __construct($name, $prefix = '', $postfix = '')
    {
        parent::__construct('div');
        $this->addClass('input-group');
        if (!empty($prefix)) {
            $this->setPrefix($prefix, $name);
        }
        $this->textBoxFactory($name);
        if ($postfix) {
            $this->setPostfix($postfix);
        }
    }

    public function __build_extra__()
    {
        if ($this->prefix) {
            $this->add($this->prefix);
        }
        $this->add($this->textBox);
        if ($this->postfix) {
            $this->add($this->postfix);
        }
    }

    public function getPrefix()
    {
        return $this->postfix;
    }

    public function getPostfix()
    {
        return $this->postfix;
    }

    public function getTextBox()
    {
        return $this->textBox;
    }

    public function setPrefix($prefix, $inputGroupId)
    {
        $this->prefix = new Tag('span', $inputGroupId . '_prefix', 'input-group-addon');
        $this->prefix->add($prefix);
    }

    public function setPostfix($object)
    {
        $this->postfix = new Tag('span', null, 'input-group-' . (is_object($object) ? 'btn' : 'addon'));
        $this->postfix->add($object);
    }

    public function textBoxFactory($name)
    {
        $this->textBox = is_object($name) ? $name : (new TextBox($name))->att('aria-describedby', $name.'_prefix');
    }
}
