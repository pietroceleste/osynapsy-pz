<?php
namespace Osynapsy\Db\Driver\Oci8;

/**
 * Description of Connection
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Connection
{
    protected $connection;
    protected $parameters = [];
    public $transaction = false;
    
    public function __construct($connectionString)
    {
        $parameters = explode(':', $connectionString);
        $this->setParameter('type', $parameters[0]);
        $this->setParameter('host', $parameters[1]);
        $this->setParameter('db', $parameters[2]);
        $this->setParameter('username', $parameters[3]);
        $this->setParameter('password', $parameters[4]);
        $this->setParameter('port', empty($parameters[5]) ? 1521 : trim($parameters[5]));
        $this->setParameter('query-parameter-dummy', 'pos');
    }

    protected function setParameter($name, $value)
    {
        $this->parameters[$name] = trim($value);
    }

    public function connect()
    {
        $connectionString = sprintf("//%s:%s/%s", $this->getParameter('host'), $this->getParameter('port'), $this->getParameter('db'));        
        $this->connection = oci_connect($this->getParameter('username'), $this->getParameter('password'), $connectionString, 'AL32UTF8');
        if (!$this->connection) {
            $err = oci_error();            
            throw new \Exception(sprintf('Connessione non riuscita (%s)', $err['message']));
        }
        $this->initConnection($this->statementFactory());
    }

    protected function initConnection($statement)
    {        
        $statement->prepare("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'")->execute();
        $statement->prepare("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '. '")->execute();
        $statement->free();
    }   

    public function getParameter($key)
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : null;
    }   

    public function statementFactory($sql = null)
    {
        $statement = new Statement($this);
        return empty($sql) ? $statement : $statement->prepare($sql);
    }

    public function __invoke()
    {
        return $this->connection;
    }

    public function quote($value)
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    public function getType()
    {
       return 'oracle';
    }

    public function getOciConnection()
    {
        return $this->connection;
    }

    public function begin()
    {
        $this->beginTransaction();
    }

    public function beginTransaction()
    {
        $this->transaction = true;
    }

    public function commit()
    {
        if (!$this->transaction) {
            throw new \Exception('No transaction in course');
        }
        oci_commit($this->connection);
    }

    public function rollback()
    {
        if (!$this->transaction) {
            throw new \Exception('No transaction in course');
        }
        oci_rollback($this->connection);
    }

    public function free()
    {
        oci_free_statement($this->statement);
    }
}
