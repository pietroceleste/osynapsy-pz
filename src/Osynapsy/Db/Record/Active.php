<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Db\Record;

use Osynapsy\Db\Driver\DboInterface;

/**
 * Active record pattern implementation
 *
 * @author   Pietro Celeste <p.celeste@osynapsy.net>
 */

abstract class Active implements RecordInterface
{
    protected $dbConnection;
    private $activeRecord = [];
    private $extendRecord = [];
    private $originalRecord = [];
    protected $recordCollection = [];
    protected $activeRecordIdx;
    protected $behavior = self::BEHAVIOR_INSERT;
    private $searchCondition = [];
    private $softDelete = [];
    protected $sequence;
    protected $table;
    protected $keys = [];
    protected $fields = [];
    protected $orderby;
    private $extensions = [];
    public $lastAutoincrementId;

    /**
     * Object constructor
     * @param array $filters array with filters
     * @param array $orderby array with orderby fields
     * @param PDO $dbo A valid dbPdo wrapper
     * @return void
     */
    public function __construct(array $filters = [], array $orderby = [], ?DboInterface $dbo = null)
    {
        $this->dbConnection = $dbo ?? dbo();
        $this->keys = $this->primaryKey();
        $this->table = $this->table();
        $this->sequence = $this->sequence();
        $this->fields = $this->fields();
        $this->softDelete = $this->softDelete();
        $this->orderby = $orderby ?: $this->orderby();
        $this->extensionFactory();
        if (!empty($filters)) {
            $this->where($filters);
        }
    }

    /**
     * Check if field exist in record. Return true if exist and false isn't exist
     *
     * @param string $field
     * @return boolean
     */
    public function fieldExists($field)
    {
        return in_array(trim($field), $this->fields);
    }

    protected function arrayIsList(array $array)
    {
        return empty($array) ? true : array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Load record from database and store in originalRecord + activeRecord
     *
     * @param $filterParameters array of parameter (key = fieldname, value = value) ex.: ['id' => 5]
     * @return void
     */
    public function where(array $filterParameters)
    {
        if (empty($filterParameters)) {
            throw new \Exception('Parameter required');
        }
        $this->reset();
        $this->searchCondition = $this->arrayIsList($filterParameters) ? $this->parameterByKeyFactory($filterParameters) : $filterParameters;
        $this->recordCollection = $this->getCollectionFromDb($this->searchCondition);
        if (empty($this->recordCollection)) {
            return $this;
        }
        $this->activeRecordIdx = 0;
        $this->activeRecord = $this->recordCollection[$this->activeRecordIdx];
        if (!empty($this->extensions)) {
            $this->activeRecord = array_merge($this->loadExtensions(), $this->activeRecord);
        }
        $this->originalRecord = $this->activeRecord;
        $this->setBehavior(self::BEHAVIOR_UPDATE);
        $this->afterFind();
        return $this;
    }

    protected function parameterByKeyFactory($keyValues)
    {
        $raw = is_array($keyValues) ? $keyValues : [$keyValues];
        if (count($this->keys) != count($raw)) {
            throw new \Exception('Values don\'t match keys '.count($this->keys).' ('.count($raw).')', 202);
        }
        $params = [];
        foreach($this->keys as $idx => $key) {
            if (!$raw[$idx]) {
                throw new \Exception(sprintf('Values key is empty [%s]', $this::class), 10);
            }
            $params[$key] = $raw[$idx];
        }
        return $params;
    }

    protected function getCollectionFromDb($filterParameters)
    {
        $conditions = $parameters  = [];
        $i = 0;
        foreach ($filterParameters as $field => $value) {
            list($condition, $conditionParameters) = $this->conditionFactory($field, $value, $i);
            $conditions[] = $condition;
            $parameters += $conditionParameters;
        }
        return $this->getDb()->findAssoc(
            sprintf(
                "SELECT * FROM %s WHERE %s ORDER BY %s",
                $this->table,
                implode(' AND ', $conditions),
                implode(', ', $this->orderby ?: $this->keys ?: ['1'])
            ),
            $parameters
        );
    }

    /**
     * Build single condition
     *
     * @param string $fieldName
     * @param mixed $value
     * @param int $idx
     */
    protected function conditionFactory($fieldName, $value, &$idx)
    {
        $values = is_array($value) ? $value : [$fieldName => $value];
        $parameters = $conditions = [];
        foreach ($values as $field => $value) {
            $parameterId = "p{$idx}";
            $conditions[] = sprintf("%s = :%s", !is_int($field) ? $field : $fieldName, $parameterId);
            $parameters[$parameterId] = $value;
            $idx++;
        }
        return ['('.implode(' OR ', $conditions).')', $parameters];
    }

    private function loadExtensions()
    {
        $values = [];
        foreach ($this->extensions as $extension) {
            $searchArray = [];
            foreach ($extension[1] as $foreignIdx => $field) {
                if (is_int($foreignIdx)) {
                    $searchArray[$field] = $this->get($this->keys[$foreignIdx]);
                    continue;
                }
                $searchArray[$foreignIdx] = $this->fieldExists($field) ? $this->get($field) : $field;
            }
            $extens = $extension[0]->where($searchArray)->get();
            $values = array_merge($values, is_array($extens) ? $extens : []);
        }
        return $values;
    }

    /**
     * Get single value from active record or get all active record
     *
     * @param string $field name to return
     * @return mixed
     */
    public function get($field = null)
    {
        if (is_null($field)) {
            return $this->activeRecord;
        }
        if (is_array($this->activeRecord) && array_key_exists($field, $this->activeRecord)) {
            return $this->activeRecord[$field];
        }
        return null;
    }

    public function getCollection(?callable $func = null)
    {
        return empty($func) ? $this->recordCollection : array_map($func, $this->recordCollection);
    }

    protected function filterColumnCollection(array $fields)
    {
        $result = [];
        $column_keys = array_flip($fields); // getting keys as values
        foreach ($this->recordCollection as $key => $values) {
            // getting only those key value pairs, which matches $column_keys
            $result[$key] = array_intersect_key($values, $column_keys);
        }
        return $result;
    }

    public function getExtension($idx = 0)
    {
        if (!array_key_exists($idx, $this->extensions)) {
            throw new \Exception(sprintf("No record exstension with idx = %s exists", $idx));
        }
        return $this->extensions[$idx][0];
    }

    protected function orderby() : array
    {
        return [];
    }

    /**
     * Set value on current active record
     *
     * @param string $field
     * @param string|int $value
     * @param string|int $defaultValue
     * @return $this
     * @throws \Exception
     */
    public function setValue($field, $value = null, $defaultValue = null)
    {
        if (empty($field)) {
            throw new \Exception("Field parameter is empty field={$field} value={$value}");
        }
        //If searched field is in actual record set activeRecord and return;
        if ($this->fieldExists($field)) {
            $this->activeRecord[$field] = $value ?? $defaultValue;
            return $this;
        }
        //If searched field is in a extension record set extendRecord and return;
        if (!empty($this->extensions) && $this->setValueInExtension($field, $value, $defaultValue)) {
            $this->extendRecord[$field] = $value;
            return $this;
        }
        //If field is not found throw a exception;
        throw new \Exception(sprintf("Field %s do not exist (%s)", $field, get_class($this)));
    }

    private function setValueInExtension($field, $value = null, $defaultValue = null)
    {
        foreach($this->extensions as $extension) {
            $record = $extension[0];
            if (!$record->fieldExists($field)) {
                continue;
            }
            $record->setValue($field, $value, $defaultValue);
            return true;
        }
        return false;
    }

    /**
     * Flush array contente and set for every element value in the record.
     *
     * @return void
     * @throws \Exception
     */
    public function setValues(array $values)
    {
        foreach($values as $field => $value) {
            $this->setValue($field, $value);
        }
        return $this;
    }

    /**
     * Save current active record on database
     *
     * @return string
     * @throws \Exception
     */
    public function save(array $values = [])
    {
        if (!empty($values)) {
            $this->setValues($values);
        }
        $this->beforeSave();
        $id = empty($this->originalRecord)? $this->insert() : $this->update();
        if (!empty($this->extensions) && !empty($this->extendRecord)) {
            $this->saveRecordExtensions();
        }
        $this->afterSave();
        return $id;
    }

    /**
     * Save current active record extension on database
     *
     * @return void
     */
    private function saveRecordExtensions()
    {
        $extendedValues = $this->extendRecord;
        foreach ($this->extensions as $extension) {
            $RecordExt   = $extension[0];
            $foreignKeys = $extension[1];
            foreach($foreignKeys as $foreignIdx => $field) {
                if (is_int($foreignIdx)) {
                    $RecordExt->setValue($field, $this->get($this->keys[$foreignIdx]));
                    continue;
                }
                $RecordExt->setValue($foreignIdx, $this->fieldExists($field) ? $this->get($field) : $field);
            }
            foreach($extendedValues as $field => $value) {
                //Intercept exception on setValue extended record;
                if (!$RecordExt->fieldExists($field)) {
                    continue;
                }
                $RecordExt->setValue($field, $value);
                $this->activeRecord[$field] = $value;
                $this->originalRecord[$field] = $value;
                unset($extendedValues[$field]);
            }
            $RecordExt->save();
        }
    }

    /**
     * Insert current active record on database
     *
     * @return string
     */
    private function insert()
    {
        $pkeys = $this->primaryKey();
        $this->beforeInsert();
        $sequenceId = $this->getSequenceNextValue();
        /*var_dump(array_intersect_key(
                $this->activeRecord,
                array_flip($this->fields()
        )));
        exit;*/
        $autoincrementId = $this->getDb()->insert(
            $this->table,
            array_intersect_key(
                $this->activeRecord,
                array_flip($this->fields())
            ),
            !empty($pkeys) && count($pkeys) === 1 ? $pkeys[0] : []
        );
        $id = !empty($autoincrementId) ? $autoincrementId : $sequenceId;
        $this->loadRecordAfterInsert($id);
        $this->afterInsert($id);
        return $id;
    }

    /**
     * After insert load record from db.
     *
     * @return string
     */
    private function loadRecordAfterInsert($id)
    {
        $this->lastAutoincrementId = $id;
        if (!empty($id) && count($this->keys) == 1) {
            $this->where([$id]);
            return;
        }
        $attributes = [];
        foreach($this->keys as $key) {
            if (!$this->activeRecord[$key]) {
                return;
            }
            $attributes[$key] = $this->activeRecord[$key];
        }
        $this->where($attributes);
    }

    /**
     * Update current active record on database
     *
     * @throws \Exception
     */
    private function update()
    {
        $this->beforeUpdate();
        $keys = $this->primaryKey();
        $fields = array_filter(
            $this->fields(),
            function($field) use ($keys) { return !in_array($field, $keys); }
        );
        $this->getDb()->update(
            $this->table,
            array_intersect_key($this->activeRecord, array_flip($fields)),
            $this->activeRecordCondition()
        );
        $this->afterUpdate();
    }

    /**
     * Delete current active record from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->beforeDelete();
        $activeRecordCondition = $this->activeRecordCondition();
        if (!empty($this->softDelete) && is_array($this->softDelete)) {
            $this->getDb()->update($this->table, $this->softDelete, $activeRecordCondition);
        } else {
            $this->getDb()->delete($this->table, $activeRecordCondition);
        }
        $this->afterDelete();
    }

    protected function activeRecordCondition()
    {
        if (empty($this->activeRecord)) {
            throw new \Exception('Active record not found', 404);
        }
        $conditions = [];
        foreach($this->keys as $key) {
            $conditions[$key] = $this->activeRecord[$key];
        }
        return $conditions;
    }

    /**
     * Reset current active record
     *
     * @return $this
     */
    public function reset()
    {
        $this->setBehavior(self::BEHAVIOR_INSERT);
        $this->activeRecord = [];
        $this->originalRecord = [];
        $this->searchCondition = [];
        return $this;
    }

    /**
     * Get current state of active record
     *
     * @return string
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * Get current db connection
     *
     * @return DbPdo object
     */
    public function getDb()
    {
        return $this->dbConnection;
    }

    /**
     * Get current state of active record
     *
     * @return string
     */
    public function getState()
    {
        return $this->behavior;
    }

    /**
     * Get next value from sequence
     *
     * @return string
     */
    protected function getSequenceNextValue()
    {
        if (empty($this->sequence)) {
            return null;
        }
        $firstKey = key(
            $this->keys
        );
        $sequenceValue = $this->getDb()->findOne("SELECT {$this->sequence}.nextval FROM dual");
        if (!empty($sequenceValue) && !empty($firstKey)) {
            $this->activeRecord[$firstKey] = $sequenceValue;
        }
        return $sequenceValue;
    }

    protected function extend($record, array $foreignKeys)
    {
        if (empty($foreignKeys)) {
            throw new \Exception("Parameter foreignKeys is empty");
        }
        $this->extensions[] = [$record, $foreignKeys];
    }

    /**
     * Get sequence
     *
     * @return string
     */
    protected function sequence()
    {
        return '';
    }

    /**
     * Active or disactive softDelete
     *
     * @return boolean
     */
    protected function softDelete()
    {
        return false;
    }

    public function __call($name, $arguments)
    {
        $cmd = substr($name, 0, 3);
        if (!in_array($cmd, ['set','get'])) {
            throw  new \Exception("Method {$name} not found");
        }
        $field = substr($name, 3);
        switch($cmd) {
            case 'set':
                return $this->setValue($field, $arguments[0]);
            case 'get':
                return $this->get($field);
        }
    }

    public function __get($field)
    {
        if (array_key_exists($field, $this->fields)) {
            $field = $this->fields[$field];
        }
        return $this->get($field);
    }

    public function __set($field, $value)
    {
        if (array_key_exists($field, $this->fields)) {
            $field = $this->fields[$field];
        }
        return $this->set($field, $value);
    }

    public function __invoke($field)
    {
        return $this->__get($field);
    }

    protected function setBehavior($behavior)
    {
        $this->behavior = $behavior;
    }

    protected function afterDelete(){}

    protected function afterFind(){}

    protected function afterInsert(){}

    protected function afterSave(){}

    protected function afterUpdate(){}

    protected function beforeDelete(){}

    protected function beforeInsert(){}

    protected function beforeSave(){}

    protected function beforeUpdate(){}

    protected function extensionFactory(){}

    abstract public function fields();

    abstract public function primaryKey();

    abstract public function table();
}
