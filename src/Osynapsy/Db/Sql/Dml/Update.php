<?php
namespace Osynapsy\Db\Sql\Dml;

use Osynapsy\Db\Sql\AbstractSql;

/**
 * Description of Insert
 *
 * @author Pietro Celeste <p.celeste@osynasy.net>
 */
class Update extends AbstractSql
{
    public function factory()
    {        
        $fields = implode(', ', array_map(fn($field) => "{$field} = :{$field}", array_keys($this->values)));
        $where = $this->whereConditionFactory($this->parameters, 'whr');
        $command = sprintf("UPDATE %s SET %s WHERE %s", $this->table, $fields, $where);
        return $command;
    }        
}
