<?php
namespace Osynapsy\Db\Sql\Dml;

use Osynapsy\Db\Sql\AbstractSql;

/**
 * Description of Insert
 *
 * @author Pietro Celeste <p.celeste@osynasy.net>
 */
class Delete extends AbstractSql
{
    public function factory()
    {
        return sprintf(
            'DELETE FROM %s WHERE %s',
            $this->table,
            $this->whereConditionFactory($this->parameters)
        );
    }        
}
