<?php
namespace Osynapsy\Db\Sql\Dml\Oracle;

use Osynapsy\Db\Sql\Dml\Insert as InsertBase;

/**
 * Description of InsertOracle
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Insert extends InsertBase
{
    public function factory()
    {
        return parent::factory() . (empty($this->parameters) ? '' : $this->returningFactory($this->parameters));
    }

    protected function returningFactory(array $returningValues)
    {
        $retValuesIdsString = implode(',', array_keys($returningValues));
        $retValuesPlaceholders = implode(',:K_', array_keys($returningValues));
        $returningClause = sprintf(' RETURNING %s INTO :K_%s', $retValuesIdsString, $retValuesPlaceholders);
        foreach (array_keys($returningValues) as $fieldId) {
            $this->values['K_'.$fieldId] = null;
        }
        return $returningClause;
    }

    public function getReturningValues($rs, $keys)
    {
        if (!is_array($keys)) {
            return $rs['K_'.$keys];
        }
        $res = [];
        foreach ($rs as $k => $v) {
            if (strpos($k,'K_') !== false) {
                $res[str_replace('K_','',$k)] = $v;
            }
        }
        return $res;
    }
}
