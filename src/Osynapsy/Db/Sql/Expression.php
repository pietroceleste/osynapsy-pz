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

    public function __construct(string $expr)
    {
        $this->expr = $expr;
    }

    public function __toString(): string
    {
        return $this->expr;
    }
}
