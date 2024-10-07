<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Db;

use Osynapsy\Db\Driver\DbOci;
use Osynapsy\Db\Driver\DbPdo;

/**
 * Description of DbFactory
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class DbFactory
{
    private $connectionPool = [];
    private $connectionIndex = [];

    /**
     * get a db connection and return
     *
     * @param idx indice della connessione da passare
     *
     * @return object
     */
    public function getConnection($idx)
    {
        return array_key_exists($idx, $this->connectionPool) ? $this->connectionPool[$idx] : false;
    }

    public function hasConnection($idx)
    {
        return array_key_exists($idx, $this->connectionPool);
    }

    /**
     * Exec a db connection and return
     *
     * @param string $connectionString contains parameter to access db (ex.: mysql:database:host:port:username:password)
     *
     * @return object
     */
    public function createConnection($connectionString)
    {
        if (array_key_exists($connectionString, $this->connectionIndex)) {
            return $this->connectionPool[$this->connectionIndex[$connectionString]];
        }
        $type = strtok($connectionString, ':');
        switch ($type) {
            case 'oracle':
                $databaseConnection = new DbOci($connectionString);
                break;
            default:
                $databaseConnection = new DbPdo($connectionString);
                break;
        }
        //Exec connection
        $res = $databaseConnection->connect();
        $currentIndex = count($this->connectionPool);
        $this->connectionIndex[$connectionString] = $currentIndex;
        $this->connectionPool[$currentIndex] = $databaseConnection;
        return $databaseConnection;
    }
}
