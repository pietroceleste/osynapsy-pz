<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Mvc;

use Osynapsy\Kernel\Route;
use Osynapsy\Http\Request;
use Osynapsy\Db\Driver\DboInterface;


interface ApplicationInterface
{
    public function __construct(Route $route, Request $request = null, DboInterface $dbo = null);

    public function run();

    public function getRoute() : Route;
}
