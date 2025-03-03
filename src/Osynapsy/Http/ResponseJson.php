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

/**
 * Implements Json response
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class ResponseJson extends Response
{
    public function __construct()
    {
        parent::__construct('text/json; charset=utf-8');
    }

    /**
     * Implements abstract method for build response
     *
     * @return json string
     */
    public function __toString()
    {
        $this->sendHeader();
        return json_encode($this->streams);
    }
}
