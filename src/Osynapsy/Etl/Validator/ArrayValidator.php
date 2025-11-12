<?php
namespace Osynapsy\Etl\Validator;


class ArrayValidator
{
    protected array $rules = [];

    public function checkNotEmpty(string ...$fields)
    {
        $this->rules[] = function ($row, $index) use ($fields) {
            foreach ($fields as $f) {
                if (!isset($row[$f]) || trim((string)$row[$f]) === '') {
                    throw new \Exception("Campo {$f} vuoto alla riga {$index}");
                }
            }
        };
        return $this;
    }

    public function checkNumeric(string ...$fields)
    {
        $this->rules[] = function ($row, $index) use ($fields) {
            foreach ($fields as $f) {
                if (!is_numeric($row[$f] ?? null)) {
                    throw new \Exception("Campo {$f} non numerico alla riga {$index}");
                }
            }
        };
        return $this;
    }

    public function checkUnique(array $fields, bool $removeDuplicates = false)
    {
        $seen = [];
        $this->rules[] = function ($row, $index) use (&$seen, $fields, $removeDuplicates) {
            $keyParts = [];
            foreach ($fields as $f) {
                $keyParts[] = $row[$f] ?? '';
            }
            $key = implode('|', $keyParts);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                return true;
            }
            if ($removeDuplicates) {
                return false;
            }
            throw new \Exception(
                "Duplicato trovato alla riga {$index}: combinazione [" . implode(',', $fields) . "] = {$key}"
            );
        };
        return $this;
    }

    public function checkRegex(string $field, string $pattern, string $message = null)
    {
        $this->rules[] = function ($row, $index) use ($field, $pattern, $message) {
            if (!preg_match($pattern, $row[$field] ?? '')) {
                $msg = $message ?? "Il campo '{$field}' non rispetta il pattern {$pattern}";
                throw new \Exception("Errore alla riga {$index}: {$msg}");
            }
        };
        return $this;
    }

    public function validate(array &$dataset)
    {
        foreach ($dataset as $i => $row) {
            foreach ($this->rules as $rule) {
                try {
                    if ($rule($row, $i + 1) === false) {
                        unset($dataset[$i]);
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        return $errors ?? [];
    }
}
