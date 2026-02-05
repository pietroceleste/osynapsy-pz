<?php
/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Db\Sequence;

use Osynapsy\Database\DboInterface;

/**
 * Description of Sequence
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
abstract class AbstractSequence
{
    protected $dbo;
    protected ?int $current = null;

    public function __construct(?DboInterface $dbo = null)
    {
        $this->dbo = $dbo ?? dbo();
    }

    // --- API pubblica ---
    public function current(): int
    {
        if ($this->current !== null) {
            return $this->current;
        }
        $this->current = $this->dbo->findOne(
            "SELECT {$this->field()}
             FROM {$this->table()}
             WHERE {$this->fieldId()} = :sequenceId",
            ['sequenceId' => $this->name()]
        );        
        return $this->current ?? $this->init();
    }
    
    private function init()
    {
        $this->dbo->insert(
            $this->table(), [
            $this->fieldId() => $this->name(),
            $this->field() => $this->startFrom()
        ]);
        return $this->startFrom();
    }

    public function next(): int
    {
        $this->increment(+1);
        return $this->current();
    }

    public function prev(): int
    {
        $this->increment(-1);
        return $this->current();
    }

      /**
     * Restituisce il valore corrente formattato
     */
    public function format(?int $value = null): string
    {
        return $this->formatPattern($value ?? $this->current());
    }

    /**
     * Definisce il pattern di formattazione
     * Default: ritorna il valore senza formattazione
     */
    protected function formatPattern(int $value): string
    {
        return (string) $value;
    }

    // --- core atomico ---
    protected function increment(int $step): void
    {
        $op = $step > 0 ? '+' : '-';
        $this->dbo->execCommand(
            "UPDATE {$this->table()}
             SET {$this->field()} = {$this->field()} {$op} :step
             WHERE {$this->fieldId()} = :name",
            ['step' => abs($step),'name' => $this->name()]
        );
        // invalido cache locale
        $this->current = null;
    }

    protected function startFrom() : int
    {
        return 1;
    }
    
     // --- punti di estensione ---
    abstract protected function table(): string;

    abstract protected function field(): string;

    abstract protected function fieldId() : string;

    abstract protected function name(): string;
}
