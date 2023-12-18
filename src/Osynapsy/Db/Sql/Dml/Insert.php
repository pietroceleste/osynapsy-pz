<?php
namespace Osynapsy\Db\Sql\Dml;

use Osynapsy\Db\Sql\AbstractSql;

/**
 * Description of Insert
 *
 * @author Pietro Celeste <p.celeste@osynasy.net>
 */
class Insert extends AbstractSql
{
    public function factory()
    {        
        $strFields = implode(',', array_keys($this->values));
        $strPlaceholders = ':'.implode(',:', array_keys($this->values));        
        return sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $strFields, $strPlaceholders);
    }        
}
