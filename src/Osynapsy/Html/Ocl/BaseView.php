<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Ocl;

use Osynapsy\Mvc\View\AbstractView;

abstract class BaseView extends AbstractView
{                   
    protected function add($part)
    {
        $this->getTemplate()->addHtml($part);
        if (is_object($part)) {
            return $part;
        }
    }
}
