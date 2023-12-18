<?php
namespace Osynapsy\Db\Sql\Dql;

use Osynapsy\Db\Sql\AbstractSql;

/**
 * Description of Select
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Select extends AbstractSql
{
    protected $fields;

    public function __construct($table, array $fields = [], array $parameters = [])
    {
        $this->fields = $fields;
        parent::__construct($table, [], $parameters);
    }

    public function factory()
    {        
        $query  = sprintf("SELECT %s FROM %s", implode(', ', $this->fields), $this->table);
        if (!empty($this->parameters)) {
            $query .= sprintf(' WHERE %s', $this->whereConditionFactory($this->parameters));
        }
        return $query;
    }
}
