<?php
namespace Osynapsy\Etl\FixedLengthFile;

use Osynapsy\Etl\Validator\ArrayValidator;

class ArrayToFixedLengthFile
{
    const TYPE_STRING = 'string';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_INTEGER = 'integer';
    const TYPE_MONEY = 'money';
    const TYPE_FLAG = 'flag';

    protected $fields = [];
    protected $currentConfId;
    protected $filecontent = [];
    protected $validateLength = true;
    protected $eol = PHP_EOL;
    protected $encoding = 'UTF-8';
    protected $validators = [];
    protected $errors = [];
    protected $totals = [];

    public function addConf($confId)
    {
        $this->currentConfId = $confId;
    }

    public function addColumn($label, $field, int $length, $default = null, $format = self::TYPE_STRING, $required = false, $pad = ' ', $paddir = STR_PAD_RIGHT)
    {
        if (empty($this->currentConfId)) {
            $this->raiseException('Non hai aggiunto un id configurazione');
        }
        if (empty($length)) {
            $this->raiseException('Non hai specificato una lunghezza per il campo '.$label);
        }
        if (!array_key_exists($this->currentConfId, $this->fields)) {
            $this->fields[$this->currentConfId] = [];
        }
        $this->fields[$this->currentConfId][] = [
            'label' => $label,
            'field' => $field,
            'length' => $length,
            'defaultValue' => $default,
            'required' => $required,
            'padding' => $pad,
            'padDirection' => $paddir,
            'format' => $format
        ];
    }

    public function append(array $dataset, $confId = null)
    {
        if (empty($confId)) {
            $confId = $this->currentConfId;
        }
        if (!array_key_exists($confId, $this->filecontent)) {
            $this->filecontent[$confId] = '';
        }
        if (array_key_exists($confId, $this->validators)) {
            $this->errors = array_merge($this->errors, $this->validators[$confId]->validate($dataset));
        }
        foreach ($dataset as $record) {
            if (!empty($record)) {
                $this->filecontent[$confId] .= $this->rowFactory($this->fields[$confId], $record).$this->eol;
            }
        }
        return $this;
    }

    public function get()
    {
        return implode('', $this->filecontent);
    }

    protected function rowFactory($fields, $record)
    {
        $result = '';
        foreach ($fields as $fieldId => $rule) {
            $fieldName = empty($rule['field']) ? $fieldId : $rule['field'];
            if (!empty($rule['required']) && !array_key_exists($fieldName, $record)) {
                $this->raiseException(sprintf('Campo %s non trovato in %s', $fieldName ,print_r($record, true)));
            }
            $value =  $record[$fieldName] ?? $rule['defaultValue'] ?? '';
            $detected_encoding = mb_detect_encoding($value);
            if ($detected_encoding !== 'ASCII' && $this->encoding != $detected_encoding) {
                $value = mb_convert_encoding($value, $this->encoding, $detected_encoding);
            }
            if (array_key_exists($fieldName, $this->totals)) {
                $this->totals[$fieldName] += $value;
            }
            $result .= $this->formatField($rule, $value);
        }
        return $result;
    }

    protected function formatField($rule, $rawValue)
    {
        $this->validateRule($rule);
        $value = $this->ruleAdjustment($this->validateValueLength($rawValue, $rule['length']), $rule);
        return $this->mbStringPad($value, $rule['length'], $rule['padding'], $rule['paddingDirection']);
    }

    protected function validateRule($rule)
    {
        if (!array_key_exists('length',$rule)) {
            $this->raiseException('Non esiste il parametro length nella regola'. print_r($rule,true));
        }
    }

    protected function ruleAdjustment($rawvalue, &$rule)
    {
        $value = str_replace(array("\n", "\t", "\r"), ' ', rtrim($rawvalue));
        if (!array_key_exists('padding',$rule)) {
            $rule['padding'] = chr(32);
        }
        if (!array_key_exists('paddingDirection',$rule)) {
            $rule['paddingDirection'] = STR_PAD_RIGHT;
        }
        if (!array_key_exists('type', $rule)) {
            $rule['type'] = 'string';
        }
        switch($rule['format']) {
            case 'decimal':
                $rule['padding'] = '0';
                $rule['paddingDirection'] = STR_PAD_LEFT;
                $value = str_replace('.', '', number_format(empty($value) ? 0 : $value, 3, '.', ''));
                break;
            case 'integer':
                $rule['padding'] = '0';
                $rule['paddingDirection'] = STR_PAD_LEFT;
                $value = round($value, 0);
                break;
            case 'money':
                $rule['padding'] = '0';
                $rule['paddingDirection'] = STR_PAD_LEFT;
                $value = str_replace('.', '', number_format(empty($value) ? 0 : $value, 2, '.', ''));
                break;
            case 'ascii':
                $value = $this->utf8ToAscii($value);
                break;
            case 'flag':
                $rule['padding'] = '0';
                $rule['length'] = 1;
                break;
        }
        return $value;
    }

    protected function validateValueLength($value, $lengthLimit)
    {
        $valueLength = $this->mbStringLenght($value);
        if ($valueLength <= $lengthLimit) {
            return $value;
        }
        if ($this->validateLength) {
            $this->raiseException(sprintf(
                'La stringa %s è troppo lunga (%s) max consentito (%s)',
                $value,
                $valueLength,
                $lengthLimit
            ));
        }
        return $this->mbSubString($value, $lengthLimit);
    }

    public function mbStringPad($input, $padLength, $pad = " ", $padStyle = STR_PAD_RIGHT)
    {
        return str_pad($input, (strlen($input) - mb_strlen($input, $this->encoding)) + $padLength, $pad, $padStyle);
    }

    public function mbStringLenght($input)
    {
        return empty($this->encoding) ? str_len($input) : mb_strlen($input,  $this->encoding);
    }

    public function mbSubString($input, int $maxlength)
    {
        return empty($this->encoding) ? str_substr($input, 0, $maxlength) : mb_substr($input, 0, $maxlength, $this->encoding);
    }

    public function save($filename)
    {
        $fullpath = $_SERVER['DOCUMENT_ROOT'] . $filename;
        if (!is_dir(dirname($fullpath))) {
            @mkdir(dirname($fullpath), 0777, true);
        }
        file_put_contents($fullpath, $this->get());
        return $filename;
    }

    protected function raiseException($message)
    {
        throw new \Exception($message);
    }

    public function cutLongValue(bool $v)
    {
        $this->validateLength = !$v;
    }

    function utf8ToAnsi($utf8_string)
    {
        // iconv translitera caratteri accentati, ignora quelli non convertibili
        $ansi_string = iconv("UTF-8", "ASCII//IGNORE", $utf8_string);

        // sostituisce eventuali caratteri ancora fuori dal range 0-255 con '?'
        $ansi_string = preg_replace('/[^\x00-\xFF]/', '?', $ansi_string);

        return $ansi_string;
    }

    function utf8ToAscii($utf8_string)
    {
         return iconv("UTF-8", "ASCII//IGNORE", $utf8_string);
    }

    function setEol($eol)
    {
        $this->eol = $eol;
        return $this;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        switch (strtoupper($encoding)) {
            case 'UTF-8':
                $this->eol = "\n";
                break;
            case 'ISO-8859-1':
            case 'WINDOWS-1252':
            case 'ANSI':
                $this->eol = "\r\n";
                break;
            default:
                // fallback neutro
                $this->eol = PHP_EOL;
        }
        return $this;
    }

    public function setValidator(ArrayValidator $validator)
    {
        $this->validators[$this->currentConfId] = $validator;
        return $this;
    }

    public function __call($method, $args)
    {
        $validator = $this->validators[$this->currentConfId] ?? null;
        // Se esiste un validator e il metodo è definito lì, inoltralo
        if (!empty($validator) && method_exists($validator, $method)) {
            $result = $validator->$method(...$args);

            // Se il metodo del validator restituisce se stesso,
            // ritorna $this per mantenere la catena fluida sull'helper
            if ($result instanceof ArrayValidator) {
                return $this;
            }

            return $result;
        }

        throw new \BadMethodCallException("Metodo {$method} non trovato né in helper né nel validator.");
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function addTotal($field)
    {
        $this->totals[$field] = 0;
    }

    public function getTotal($field)
    {
        return $this->totals[$field];
    }
}
