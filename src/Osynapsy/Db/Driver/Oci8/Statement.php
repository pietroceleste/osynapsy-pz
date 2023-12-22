<?php
namespace Osynapsy\Db\Driver\Oci8;

/**
 * Description of Statement
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Statement
{
    private $connection;
    private $statement;
    private $transaction;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function prepare($sql)
    {
        $this->statement = oci_parse($this->connection->getOciConnection(), $sql);
        return $this;
    }

    public function execute(array $parameters = [])
    {
        if (!empty($parameters)) {
            $this->bindParameters($this->statement, $parameters);
        }
        if (!@oci_execute($this->statement, $this->connection->transaction ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS)) {
            $this->raiseException($this->statement, ['message', 'sqltext'], print_r($parameters, true));
        }
        return $parameters;
    }

    protected function bindParameters($statement, &$parameters)
    {
        foreach($parameters as $parId => $val) {
             // oci_bind_by_name($statement, $parId, $val) does not work
            // because it binds each placeholder to the same location: $val
            // instead use the actual location of the data: $parameters[$key]
            $l = strlen($val) > 255 ? strlen($val) : 255;
            oci_bind_by_name($statement, sprintf(':%s', $parId), $parameters[$parId], $l);
        }
    }

    protected function get()
    {
        return $this->statement;
    }

    public function fetchAll($method = OCI_ASSOC)
    {
        oci_fetch_all($this->statement, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW|OCI_RETURN_NULLS|OCI_RETURN_LOBS|$method);        
        return $result;
    }

    public function fetch($method = OCI_ASSOC)
    {
        return oci_fetch_array($this->statement, $method | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    }

    public function free()
    {
        oci_free_statement($this->statement);
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

    public function __invoke()
    {
        return $this->statement;
    }
}
