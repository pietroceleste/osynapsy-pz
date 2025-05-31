<?php
namespace Osynapsy\Db\Sql\Dml;

use Osynapsy\Db\Sql\AbstractSql;
use Osynapsy\Db\Sql\Expression;

/**
 * Description of Insert
 *
 * @author Pietro Celeste <p.celeste@osynasy.net>
 */
class Insert extends AbstractSql
{
    public function factory()
    {
        $fields = array_keys($this->values);
        $placeholders = array_map(
            function ($field) {
                $value = $this->values[$field];
                return $value instanceof Expression ? (string) $value : ':' . $field;
            },
            $fields
        );
        return sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, implode(',', $fields), implode(',', $placeholders));
    }
}
