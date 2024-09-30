<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Data;

/**
 * Description of Tree
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Tree
{
    const POSITION_BEGIN = 1;
    const POSITION_BETWEEN = 2;
    const POSITION_END = 3;

    private $keyId;
    private $keyParent;
    private $keyIsOpen;
    private $openNodes = [];
    private $dataSet;
    private $tree;
    protected $limitLevel;

    public function __construct($idKey, $parentKey, $isOpenKey = null, array $dataSet = [], int $limitToLevel = 0)
    {
        $this->keyId = $idKey;
        $this->keyParent = $parentKey;
        $this->keyIsOpen = $isOpenKey;
        $this->setDataset($dataSet);
        $this->setLimitToLevel($limitToLevel);
    }

    protected function init()
    {
        $rawDataSet = [];
        foreach ($this->dataSet as $rec){
            $rawDataSet[empty($rec[$this->keyParent]) ? 0 : $rec[$this->keyParent]][] = $rec;
        }
        return $rawDataSet;
    }

    protected function build(&$rawDataSet, $parentId = 0, $level = 0)
    {
        $branch = [];
        $lastIdx = count($rawDataSet[$parentId]) - 1;
        foreach ($rawDataSet[$parentId] as $idx => $child){
            $childId = $child[$this->keyId];
            if (!empty($level)) {
                //$child['_parent'] =& $rawDataSet[$parentId];
            }
            if (!empty($this->limitLevel) &&  $level > $this->limitLevel) {
                continue;
            }
            $child['_level'] = $level;
            $child['_position'] = $this->setPosition($idx, $lastIdx);
            if(!empty($rawDataSet[$childId])){
               $child['_childrens'] = $this->build($rawDataSet, $childId, $level + 1);
            }
            $branch[$child[$this->keyId]] = $child;
        }
        return $branch;
    }

    public function get()
    {
        if (!is_null($this->tree)) {
            return $this->tree;
        }
        $rawDataSet = $this->init();
        $this->tree = $this->build($rawDataSet);
        return $this->tree;
    }

    public function openNodes(&$child)
    {
        if (empty($child)) {
            return;
        }
        $child[$this->keyIsOpen] = 1;
        if (empty($child['parent'])) {
            return;
        }
        $this->openNodes($child['parent']);
    }

    public function setDataset(array $dataset)
    {
        $this->dataSet = $dataset;
    }

    /**
     * Calcolo in che posizione si trova l'elemento (In testa = 1, nel mezzo = 2, alla fine = 99);
     *
     * @param int $idx posizione dell'elemento
     * @param int $last posizione dell'ultimo elemento
     * @return int
     */
    private function setPosition($idx, $last)
    {
        if ($idx === $last) {
            return self::POSITION_END;
        }
        if (empty($idx)) {
            return self::POSITION_BEGIN;
        }
        return self::POSITION_BETWEEN;
    }
    
    public function setLimitToLevel(int $limitToLevel)
    {
        $this->limitLevel = $limitToLevel;
    }
}
