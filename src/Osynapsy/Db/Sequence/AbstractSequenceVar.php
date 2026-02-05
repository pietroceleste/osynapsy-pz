<?php
namespace Osynapsy\Db\Sequence;

use Osynapsy\Sql\Expression as SqlExpression;
use Osynapsy\Database\DboInterface;

/**
 * Description of AbstractSequenceVar
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
abstract class AbstractSequenceVar
{
    protected PDO $pdo;
    protected string $name;
    protected ?int $current = null;

    public function __construct(string $name, ?DboInterface $dbo = null)
    {
        $this->dbo  = $dbo ?? dbo();
        $this->name = $name;
    }

    // --- API ---
    public function current(): int
    {
        if ($this->current !== null) {
            return $this->current;
        }

        $stmt = $this->dbo->prepare(
            "SELECT {$this->field()} FROM {$this->table()} WHERE name = :name"
        );
        $stmt->execute(['name' => $this->name]);

        return $this->current = (int)$stmt->fetchColumn();
    }

    public function next(): int
    {
        $this->increment(+1);
        return $this->current = $this->current();
    }

    public function prev(): int
    {
        $this->increment(-1);
        return $this->current = $this->current();
    }

    protected function increment(int $step): void
    {
        $op = $step > 0 ? '+' : '-';
        $this->dbo->update(
            $this->table,
            [$this->field() => new SqlExpression($this->field() ." {$op} ". (abs($step)))],
            [$this->fieldId() => $this->name]
        );
        $this->current = null;
    }

    // --- da definire nella sottoclasse ---
    abstract protected function table(): string;

    abstract protected function field(): string;

    abstract protected function fieldId(): string;
}
