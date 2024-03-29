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

use Osynapsy\Http\Request;
use Osynapsy\Db\DbFactory;
use Osynapsy\Mvc\ApplicationInterface;

interface ControllerInterface
{
    public function __construct(Request $request = null, DbFactory $db = null, ApplicationInterface $appController = null);

    public function getResponse();

    public function setModel($model);
}