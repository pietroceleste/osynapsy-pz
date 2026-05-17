<?php
namespace Osynapsy\Db\Sql;

/**
 * Description of Expression
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Expression
{
    public string $expr;
    public string $operator;

    public function __construct(string $expr, string $operator = '=')
    {
        $this->expr = $expr;
        $this->operator = $operator;
    }

    public function __toString(): string
    {
        return $this->expr;
    }

    public function getOperator()
    {
        return $this->operator;
    }
}
