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

/**
 * Oci wrap class
 *
 * PHP Version 5
 *
 * @category Driver
 * @package  Opensymap
 * @author   Pietro Celeste <p.celeste@osynapsy.org>
 * @license  GPL http://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     http://docs.osynapsy.org/ref/DbOci
 */
class DbOci implements InterfaceDbo
{
    private $parameters = array();
    private $__cur = null;
    public  $backticks = '"';
    public  $cn = null;
    private $__transaction = false;
    //private $rs;

    public function __construct($osyConnectionStringFormat)
    {
        $parameters = explode(':',$osyConnectionStringFormat);
        $this->setParameter('type', $parameters[0]);
        $this->setParameter('host', $parameters[1]);
        $this->setParameter('db', $parameters[2]);
        $this->setParameter('username', $parameters[3]);
        $this->setParameter('password', $parameters[4]);
        $this->setParameter('port', empty($par[5]) ? 1521 : trim($par[5]));
        $this->setParameter('query-parameter-dummy', 'pos');
    }

    public function begin()
    {
        $this->beginTransaction();
    }

    public function beginTransaction()
    {
        $this->__transaction = true;
    }

    public function columnCount()
    {
       return $this->__cur->columnCount();
    }

    public function commit()
    {
        oci_commit($this->cn );
    }

    public function rollback()
    {
        oci_rollback($this->cn );
    }

    public function quote($value)
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    public function connect()
    {
        $connectionString = sprintf("//%s:%s/%s", $this->getParameter('host'), $this->getParameter('port'), $this->getParameter('db'));
        $this->cn = oci_connect($this->getParameter('username'), $this->getParameter('password'), $connectionString, 'AL32UTF8');
        if (!$this->cn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        $this->execCommand("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
        $this->execCommand('alter session set NLS_NUMERIC_CHARACTERS = ". "');
    }

    public function getParameter($key)
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : null;
    }

    function getType()
    {
       return 'oracle';
    }

    //Metodo che setta il parametri della connessione
    function setParameter($name, $value)
    {
        $this->parameters[$name] = trim($value);
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

    public function execMulti($cmd, $par)
    {
        $this->beginTransaction();
        $s = $this->prepare($cmd);
        foreach ($par as $rec) {
            $s->execute($rec);
        }
        $this->commit();
    }

    public function execCommand($cmd, $par = null, $rs_return = true)
    {
        $rs = oci_parse($this->cn, $cmd);
        if (!$rs) {
            $e = oci_error($this->cn);  // For oci_parse errors pass the connection handle
            throw new \Exception($e['message']);
        }
        if (!empty($par) && is_array($par)) {
            foreach ($par as $k => $v) {
                $$k = $v;
                // oci_bind_by_name($rs, $k, $v) does not work
                // because it binds each placeholder to the same location: $v
                // instead use the actual location of the data: $$k
                $l = strlen($v) > 255 ? strlen($v) : 255;
                oci_bind_by_name($rs, ':'.$k, $$k, $l);
            }
        }
        $ok = $this->__transaction ? @oci_execute($rs, OCI_NO_AUTO_COMMIT) : @oci_execute($rs);
        if (!$ok) {
            $e = oci_error($rs);  // For oci_parse errors pass the connection handle
            throw new \Exception($e['message'].PHP_EOL.$e['sqltext'].PHP_EOL.print_r($par,true));
        }
        if ($rs_return) {
            return $rs;
        }
        foreach ($par as $k=>$v) {
            $par[$k] = $$k;
        }
        oci_free_statement($rs);
        return $par;
    }

    public function execQuery($sql, $par = null, $fetchMethod = null)
    {
        $this->__cur = $this->execCommand($sql, $par);
        switch ($fetchMethod) {
            case 'BOTH':
                $fetchMethod = OCI_BOTH;
                break;
            case 'NUM':
                $fetchMethod = OCI_NUM;
                break;
            default:
                $fetchMethod = OCI_ASSOC;
                break;
        }
        oci_fetch_all($this->__cur, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW|OCI_RETURN_NULLS|OCI_RETURN_LOBS|$fetchMethod);
        //oci_free_statement($cur);
        return $result;
    }

    public function execQueryNum($sql, $par = null)
    {
        return $this->execQuery($sql, $par, 'NUM');
    }

    public function getIterator($rs, $par = null, $method = null)
    {
	    if (is_string($rs)) {
	        $rs = $this->execCommand($rs, $par);
	    } else {
	        $method = $par;
	    }
	    switch($method) {
	        case 'BOTH':
	            $method = OCI_BOTH;
	            break;
	        case 'NUM':
	            $method = OCI_NUM;
	            break;
	        default:
	            $method = OCI_ASSOC;
	            break;
	    }
	    while ($record = oci_fetch_array($rs, $method | OCI_RETURN_NULLS)) {
	        yield $record;
	    }
	    $this->freeRs($rs);
    }

    public function query($sql)
    {
        return $this->execCommand($sql);
    }

    public function getFirst($rs, $par = null, $method = null)
    {
	    if (is_string($rs)) {
	        $rs = $this->execCommand($rs, $par);
	    } else {
	        $method = $par;
	    }
	    switch($method) {
	        case 'BOTH':
	            $method = OCI_BOTH;
	            break;
	        case 'NUM':
	            $method = OCI_NUM;
	            break;
	        default:
	            $method = OCI_ASSOC;
	            break;
	    }
	    $record = oci_fetch_array($rs, $method | OCI_RETURN_NULLS);
	    $this->freeRs($rs);
	    return $record;
    }

    public function fetchAll2($rs)
    {
        oci_fetch_all($rs, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW|OCI_ASSOC|OCI_RETURN_NULLS|OCI_RETURN_LOBS);
        return $res;
    }

    public function fetchAll($rs)
    {
        $result = array();
        while ($record = oci_fetch_array($rs, OCI_ASSOC|OCI_RETURN_NULLS)) {
            $result[] = $record;
        }
        return $result;
    }

    public function execUnique($sql, $par = null, $mth = 'NUM')
    {
       $res = $this->execQuery($sql, $par, $mth);
       if (empty($res)) return null;
       return (count($res[0])==1) ? $res[0][0] : $res[0];
    }

    public function execOneAssoc($sql, $par = null)
    {
        return $this->execUnique($sql, $par, 'ASSOC');
    }

    public function getColumns($stmt = null)
    {
        $stmt = is_null($stmt) ? $this->__cur : $stmt;
        $cols = array();
        $ncol = oci_num_fields($stmt);
        for ($i = 1; $i <= $ncol; $i++) {
            $cols[] = array(
                'native_type' => oci_field_type($stmt,$i),
                'flags' => array(),
                'name' => oci_field_name($stmt,$i),
                'len' => oci_field_size($stmt,$i),
                'pdo_type' => oci_field_type_raw($stmt,$i)
            );
        }
        return $cols;
    }

    public function insert($table, array $values, $keys = array())
    {
        $command  = 'INSERT INTO '.$table;
        $command .= '('.implode(',', array_keys($values)).')';
        $command .= ' VALUES ';
        $command .= '(:'.implode(',:',array_keys($values)).')';
        if (is_array($keys) && !empty($keys)) {
            $command .= ' RETURNING ';
            $command .= implode(',',array_keys($keys));
            $command .= ' INTO ';
            $command .= ':KEY_'.implode(',:KEY_',array_keys($keys));
            foreach ($keys as $k => $v) {
                $values['KEY_'.$k] = null;
            }
        }
        $values = $this->execCommand($command, $values, false);
        $res = array();
        foreach ($values as $k => $v) {
            if (strpos($k,'KEY_') !== false) {
                $res[str_replace('KEY_','',$k)] = $v;
            }
        }
        return $res;
    }

    public function update($table, array $values, array $condition)
    {
        $fields = array();
        $where = array();
        foreach ($values as $field => $value) {
            $fields[] = "{$field} = :{$field}";
        }
        foreach ($condition as $field => $value) {
            if (is_null($value)) {
                $where[] = "$field is null";
                continue;
            }
            $where[] = "$field = :WHERE_{$field}";
            $values['WHERE_'.$field] = $value;
        }
        $cmd = 'UPDATE '.$table.' SET ';
        $cmd .= implode(', ',$fields);
        $cmd .= ' WHERE ';
        $cmd .= implode(' AND ',$where);
        return $this->execCommand($cmd, $values);
    }

    public function delete($table, array $conditions)
    {
        $where = [];
        foreach($conditions as $k => $v){
            $where[] = "{$k} = :{$k}";
        }
        $this->execCommand(
            sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $where)),
            $conditions
        );
    }

    public function replace($table, array $args, array $conditions)
    {
        $result = $this->select($table, ['count(*) AS NUMROWS'], $conditions);
        if (!empty($result) && !empty($result[0]) && !empty($result[0]['NUMROWS'])) {
            $this->update($table, $args, $conditions);
            return;
        }
        $this->insert($table, array_merge($args, $conditions));
    }

    public function select($table, array $fields, array $condition)
    {
        $where = array();
        foreach ($condition as $field => $value) {
            if (is_null($value)) {
                $where[] = "$field is null";
                continue;
            }
            $where[] = "$field = :WHERE_{$field}";
            $values['WHERE_'.$field] = $value;
        }
        $cmd = 'SELECT '.implode(', ',$fields);
        $cmd .= ' FROM '.$table;
        $cmd .= ' WHERE ';
        $cmd .= implode(' AND ',$where);
        return $this->execQuery($cmd, $values, 'ASSOC');
    }

    public function freeRs($rs = null)
    {
        if ($rs === null) {
            $rs = $this->__cur;
        }
        if ($rs) {
            oci_free_statement($rs);
        }
    }

    public function close()
    {
        oci_close($this->cn);
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
/*End class*/
}
