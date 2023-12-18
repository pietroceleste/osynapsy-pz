<?php
namespace Osynapsy\Db\Sql;

/**
 * Description of AbstractSql
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
abstract class AbstractSql
{
    protected $table;
    protected $values;
    protected $parameters;
    
    public function __construct($table, array $values = [], array $parameters = [])
    {
        $this->table = $table;
        $this->values = $values;
        $this->parameters = $parameters;
    }
    protected function whereConditionFactory(array $conditions, $prefix = '')
    {
        if (empty($conditions)) {
            throw new \Exception('Conditions parameter is empty.');
        }
        $filters = [];
        foreach($conditions as $field => $value) {
            if (is_null($value)) {
                $filters[] = $this->isNullClause($field);
                continue;
            }
            if (is_array($value)) {
                $filters[] = $this->inClauseFactory($field, $value);
                continue;
            }
            $filters[] = $field . " = :". $prefix . $field;
            $this->values[$prefix . $field] = $value;
        }        
        return implode(' AND ', $filters);
    }

    protected function isNullClause($field)
    {
        return sprintf('%s is null', $field);
    }

    protected function inClauseFactory($field, array $values)
    {
        return sprintf("%s in ('%s')", $field, implode("','", $values));
    }
    
    public function &getValues()
    {
        return $this->values;
    }
    
    public abstract function factory();
    
    public function __toString()
    {
        return $this->factory();
    }
}
