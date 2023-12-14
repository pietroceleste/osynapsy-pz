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

    public function insert($table, array $values, $rawkeys = [])
    {
        $keys = is_array($rawkeys) ? $rawkeys : [$rawkeys => null];
        $command = sprintf(
            'INSERT INTO %s (%s) VALUES (:%s)',
            $table,
            implode(',', array_keys($values)),
            implode(',:', array_keys($values))
        );
        if (is_array($keys) && !empty($keys)) {
            $command .= sprintf(' RETURNING %s INTO :K_%s', implode(',',array_keys($keys)), implode(',:K_',array_keys($keys)));
            foreach (array_keys($keys) as $keyId) {
                $values['K_'.$keyId] = null;
            }
        }
        //die($command);
        $result = $this->execCommand($command, $values, false);        
        return $this->getReturningValues($result, $rawkeys);
    }

    protected function getReturningValues($rs, $keys)
    {
        if (!is_array($keys)) {
            return $rs['K_'.$keys];
        }
        $res = [];
        foreach ($rs as $k => $v) {
            if (strpos($k,'K_') !== false) {
                $res[str_replace('K_','',$k)] = $v;
            }
        }
        return $res;
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
        $fields = implode(', ', array_map(fn($field) => "{$field} = :{$field}", array_keys($values)));
        list($where, $whereValues) = $this->whereConditionFactory($conditions, 'whr');
        $command = sprintf("UPDATE %s SET %s WHERE %s", $table, $fields, $where);        
        return $this->execCommand($command, array_merge($values, $whereValues));
    }

    public function delete($table, array $conditions)
    {
        list($where,) = $this->whereConditionFactory($conditions);
        $command = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $this->execCommand($command, $conditions);
    }   

    public function select($table, array $rawfields, array $filters = ['1 = 1'])
    {
        list($where,) = $this->whereFactory($filters);
        $fields = implode(', ', $rawfields);
        $query = sprintf("SELECT %s FROM %s WHERE %s", $fields, $table, $where);
        return $this->execQuery($query, $filters, 'ASSOC');
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

    protected function whereConditionFactory(array $conditions, $prefix = '')
    {
        if (empty($conditions)) {
            throw new \Exception('Conditions parameter is empty.');
        }
        $filters = $newvalues = [];
        foreach($conditions as $field => $value) {
            if (is_null($value)) {
                $filters[] = $this->isNullClause($field);
                continue;
            }
            if (is_array($value)) {
                $filters[] = $this->inClauseFactory($field, $value);
                continue;
            }
            $filters[] = $field . " = :". $prefix . $field;
            $newvalues[$prefix . $field] = $value; 
        }        
        return [implode(' AND ', $filters), $newvalues];
    }

    protected function isNullClause($field)
    {
        return sprintf('%s is null', $field);
    }

    protected function inClauseFactory($field, array $values)
    {
        return sprintf("%s in ('%s')", $field, implode("','", $values));
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
    
    protected function raiseException($object, array $errorkeys = ['message'], $postfix = null)
    {
        $err = oci_error($object);  // For oci_parse errors pass the connection handle
        $errorMessage = [];
        foreach($errorkeys as $errorkey) {
            $errorMessage[] = $err[$errorkey];
        }
        if (!empty($postfix)) {
            $errorMessage[] = $postfix;
        }
        throw new \Exception(implode(PHP_EOL, $errorMessage));
    }

    public function __call($method, array $parameters)
    {        
        if (!method_exists($this->connection, $method)) {
            throw new \Exception(sprintf('Method %s do not exists', $method));
        }
        return call_user_func_array([$this->connection, $method], $parameters);
    }
}
