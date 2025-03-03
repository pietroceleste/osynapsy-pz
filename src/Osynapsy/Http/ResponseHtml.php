<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Http;

use Osynapsy\Mvc\View\Template;

class ResponseHtml extends Response
{
    public $template;

    public function __construct()
    {
        parent::__construct('text/html');
        $this->template = new Template(request('page.route')->template);
    }

    public function writeStream($content, $id = 'main')
    {
        $this->template->addHtml($content);
    }

    public function __toString()
    {
        $this->sendHeader();
        parent::writeStream(strval($this->template));
        return implode(PHP_EOL, array_map(fn($stream) => is_array($stream) ? implode('',$stream) : $stream, $this->streams));
    }
}
