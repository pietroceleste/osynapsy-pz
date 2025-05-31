<?php
namespace Osynapsy\Db\Sql\Dml;

use Osynapsy\Db\Sql\AbstractSql;
use Osynapsy\Db\Sql\Expression;

/**
 * Description of Insert
 *
 * @author Pietro Celeste <p.celeste@osynasy.net>
 */
class Update extends AbstractSql
{
    public function factory()
    {
        //$fields = implode(', ', array_map(fn($field) => "{$field} = :{$field}", array_keys($this->values)));
        $fields = implode(', ', array_map(function ($field) {
            $value = $this->values[$field];
            return sprintf('%s = %s', $field, $value instanceof Expression ? (string) $value : ":$field");
        }, array_keys($this->values)));
        $where = $this->whereConditionFactory($this->parameters, 'whr');
        $command = sprintf("UPDATE %s SET %s WHERE %s", $this->table, $fields, $where);
        return $command;
    }
}
