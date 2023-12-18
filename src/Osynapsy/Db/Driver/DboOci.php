<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Db\Driver;

use Osynapsy\Db\Driver\Oci8\Connection;
use Osynapsy\Db\Driver\Oci8\Statement;
use Osynapsy\Db\Sql\Dml\Oracle\Insert;
use Osynapsy\Db\Sql\Dml\Update;
use Osynapsy\Db\Sql\Dml\Delete;
use Osynapsy\Db\Sql\Dql\Select;

/**
 * Oci wrap class
 *
 * PHP Version 8
 *
 * @category Driver
 * @package  Opensymap
 * @author   Pietro Celeste <p.celeste@osynapsy.org>
 * @license  GPL http://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     http://docs.osynapsy.org/ref/DbOci
 */
class DboOci implements DboInterface
{   
    const FETCH_METHOD = [
        'NUM' => OCI_NUM,
        'ASSOC' => OCI_ASSOC,
        'BOTH' => OCI_BOTH
    ];

    public  $backticks = '"';
    public  $connection;
    protected $statement;
    protected $parameters = [];
    protected $transaction = false;

    public function __construct($osyConnectionStringFormat)
    {
        $this->connection = new Connection($osyConnectionStringFormat);
    }        

    public function execCommand($command, array $parameters = [], $returnStatement = true)
    {
        $statement = $this->statementFactory($command);
        $result = $statement->execute($parameters);
        return $returnStatement ? $statement : $result;
    }
    
    public function execMulti($command, array $records)
    {
        $this->beginTransaction();
        $statement = $this->statementFactory($command);
        foreach ($records as $record) {
            $statement->execute($record);
        }
        $this->commit();
    }

    public function find($sql, array $parameters = [], $fetchMethodId = 'ASSOC')
    {
        $statement = $this->statementFactory($sql);
        $statement->execute($parameters);
        return $statement->fetchAll(self::FETCH_METHOD[$fetchMethodId]);
    }

    public function findOne($sql, array $parameters = [], $fetchMethodId = 'NUM')
    {
       $statement = $this->statementFactory($sql);
       $statement->execute($parameters);
       $result = $statement->fetch(self::FETCH_METHOD[$fetchMethodId]);
       return empty($result) ? null : (count($result) == 1 ? $result[0] : $result); 
    }
    
    public function getIterator($query, $parameters = [], $method = 'ASSOC')
    {	    
        $rs = $this->execCommand($query, $parameters);
        while ($record = $this->fetch($rs, $method)) {
            yield $record;
        }
        $this->freeRs($rs);
    }

    public function query($sql)
    {
        return $this->execCommand($sql);
    }        

    public function insert($table, array $values, $returningValues = [])
    {
        $handle = new Insert($table, $values, is_array($returningValues) ? $returningValues : [$returningValues => null]);
        $command = strval($handle);
        $result = $this->execCommand($command, $handle->getValues(), false);
        return $handle->getReturningValues($result, $returningValues);
    }

    //Prendo l'ultimo valore di un campo autoincrement dopo l'inserimento
    public function lastInsertId($arg)
    {
        foreach ($arg as $k => $v) {
            if (strpos('KEY_',$k) !== false) {
                return $v;
            }
        }
    }

    public function update($table, array $values, array $conditions)
    {
        $cmdHandle = new Update($table, $values, $conditions);
        return $this->execCommand(strval($cmdHandle), $cmdHandle->getValues());
    }

    public function delete($table, array $conditions)
    {
        $cmdHandle = new Delete($table, [], $conditions);
        $this->execCommand(strval($cmdHandle), $cmdHandle->getValues());
    }

    public function select($table, array $rawfields, array $filters = [])
    {
        $qryHandle = new Select($table, $rawfields, $filters);
        return $this->execQuery(strval($qryHandle), $qryHandle->getValues(), 'ASSOC');
    }

    public function replace($table, array $args, array $conditions, $key = null)
    {
        $result = $this->select($table, empty($key) ? ['count(*) AS NUMROWS'] : [$key, '1 as NUMROWS'], $conditions);
        if (!empty($result) && !empty($result[0]) && !empty($result[0]['NUMROWS'])) {
            $this->update($table, $args, $conditions);
            return empty($key) ? null : $result[0][strtoupper($key)];
        }
        $rs = $this->insert($table, array_merge($args, $conditions), empty($key) ? [] : [$key => null]);
        return empty($key) ? null : $rs[strtoupper($key)];
    }

    public function freeRs($rs)
    {        
        oci_free_statement($rs);
    }

    public function close()
    {
        oci_close($this->connection);
    }

    public function dateToSql($date)
    {
        $app = explode('/',$date);
        if (count($app) === 3){
            return "{$app[2]}-{$app[1]}-{$app[0]}";
        }
        return $date;
    }

    public function setDateFormat($format = 'YYYY-MM-DD')
    {
        $this->execCommand("ALTER SESSION SET NLS_DATE_FORMAT = '{$format}'");
    }        

    public function __call($method, array $parameters)
    {        
        if (!method_exists($this->connection, $method)) {
            throw new \Exception(sprintf('Method %s do not exists', $method));
        }
        return call_user_func_array([$this->connection, $method], $parameters);
    }
}
