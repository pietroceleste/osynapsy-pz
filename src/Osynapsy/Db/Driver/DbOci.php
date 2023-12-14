<?php
namespace Osynapsy\Db\Driver;

/**
 * Description of DbOci
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class DbOci extends DboOci
{
    public function execQuery($sql, $parameters = null, $fetchMethodId = 'ASSOC')
    {        
        return $this->find($sql, $parameters ?? [], $fetchMethodId);
    }

    public function execQueryNum($sql, $parameters = [])
    {
        return $this->execQuery($sql, $parameters, 'NUM');
    }

    public function execUnique($sql, $parameters = null, $fetchMethodId = 'NUM')
    {
        return $this->findOne($sql, $parameters ?? [], $fetchMethodId);
    }

    public function execOneAssoc($sql, $par = null)
    {
        return $this->execUnique($sql, $par, 'ASSOC');
    }
}
