<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Ocl\TreeBox;

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component as AbstractComponent;
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Data\Tree as TreeDataStructure;


/**
 * Description of TreeBox
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class TreeBox extends AbstractComponent
{
    private $nodeOpenIds = [];
    private $refreshOnClick = [];
    private $refreshOnOpen = [];
    private $dataTree;
    protected $limitLevel = 0;

    const CLASS_SELECTED_LABEL = 'osy-treebox-label-selected';
    const ICON_NODE_CONNECTOR_EMPTY = '<span class="tree tree-null">&nbsp;</span>';
    const ICON_NODE_CONNECTOR_LINE = '<span class="tree tree-con-4">&nbsp;</span>';
    const ROOT_ID = 0;

    public function __construct($id)
    {
        parent::__construct('div', $id);
        $this->add(new HiddenBox("{$id}_sel"))->addClass('selectedNode');
        $this->add(new HiddenBox("{$id}_opn"))->addClass('openNodes');
        $this->addClass('osy-treebox');
        $this->requireJs('Ocl/TreeBox/ocl-treebox.js');
        $this->requireCss('Ocl/TreeBox/ocl-treebox.css');
        $this->nodeOpenIds = explode(',', str_replace(['][','[',']'],[',','',''], $_REQUEST[$id.'_opn'] ?? ''));
    }

    public function __build_extra__()
    {
        if (empty($this->dataTree)) {
            return;
        }
        foreach ($this->dataTree->get() as $node) {
            $this->add($this->nodeFactory($node));
        }
        if (!empty($this->refreshOnClick)) {
            $this->att('data-refresh-on-click', implode(',', $this->refreshOnClick));
        }
        if (!empty($this->refreshOnOpen)) {
            $this->att('data-refresh-on-open', implode(',', $this->refreshOnOpen));
        }
    }

    protected function nodeFactory($item, $icons = [])
    {
        if ($item['_level'] > -1){
            $icons[$item['_level']] = $item['_position'] === TreeDataStructure::POSITION_END ? self::ICON_NODE_CONNECTOR_EMPTY: self::ICON_NODE_CONNECTOR_LINE;
        }
        return empty($item['_childrens']) ? $this->leafFactory($item, $icons) : $this->branchFactory($item, $icons);
    }

    protected function leafFactory($item, $icons)
    {
       $leaf = new Tag('div', null, 'osy-treebox-leaf');
       if (!empty($this->refreshOnClick)) {
           $leaf->addClass('osy-treebox-node');
           $leaf->att(['data-level' => $item['_level'], 'data-node-id' => $item[0]]);
       }
       $leaf->add($this->iconFactory($item, $icons));
       $leaf->add(new Tag('span', null, 'osy-treebox-node-label'))
            ->add(new Tag('span', null, 'osy-treebox-label'))
            ->add($item[1]);
       if (count($item) > 4) {
           $leaf->add($this->commandFactory($item));
       }
       return $leaf;
    }

    protected function branchFactory($item, $icons)
    {
        $branch = new Tag('div', null, 'osy-treebox-branch');
        if (!empty($this->refreshOnClick)) {
            $branch->addClass('osy-treebox-node');
            $branch->att(['data-level' => $item['_level'], 'data-node-id' => $item[0]]);
        }
        $branch->add($this->branchHeadFactory($item, $icons));
        $branch->add($this->branchBodyFactory($item, $icons));
        return $branch;
    }

    protected function branchHeadFactory($item, $icons)
    {
        $head = new Tag('div', null, 'osy-treebox-node-head');
        $head->add($this->iconFactory($item, $icons));
        $label = $head->add(new Tag('span', '', 'osy-treebox-node-label'));
        $label->add($item[1]);
        if (!empty($this->refreshOnClick)) {
           $label->addClass('osy-treebox-label');
        }
        if (count($item) > 4) {
           $head->add($this->commandFactory($item));
       }
        return $head;
    }

    protected function branchBodyFactory($item, $icons)
    {
        $branchBody = new Tag('div', null, 'osy-treebox-branch-body');
        if (!in_array($item[0], $this->nodeOpenIds) && ($item[3] != '1')) {
            $branchBody->addClass('d-none hidden');
        }
        foreach ($item['_childrens'] as $node) {
            $branchBody->add($this->nodeFactory($node, $icons));
        }
        return $branchBody;
    }

    private function iconFactory($node, $icons = [])
    {
        $class = "osy-treebox-branch-command tree-plus-".(!empty($node['_level']) && $node['_position'] === TreeDataStructure::POSITION_BEGIN ? TreeDataStructure::POSITION_BETWEEN : $node['_position']);
        if (empty($node['_childrens'])){
            $class = "tree-con-{$node['_position']}";
        } elseif (in_array($node[0], $this->nodeOpenIds) || !empty($node[3])) { //If node is open load minus icon
            $class .= ' minus';
        }
        //Sovrascrivo l'ultima icona con il l'icona/segmento corrispondente al comando / posizione
        $icons[$node['_level']] = sprintf('<span class="tree %s">&nbsp;</span>', $class);
        return implode('',$icons);
    }

    private function commandFactory($node)
    {
        $dummy = new Tag('dummy');
        if (count($node) < 4){
            return $dummy;
        }
        foreach($node as $i => $command) {
            if ($i <= 3 || empty($command) || !is_int($i)) {
                continue;
            }
            $dummy->add(new Tag('span', null, 'osy-treebox-node-command'))->add($command);
        }
        return $dummy;
    }

    public function getPath()
    {
        return $this->pathSelected;
    }

    public function onClickRefresh($componentId)
    {
        $this->refreshOnClick[] = $componentId;
        return $this;
    }

    public function onOpenRefresh($componentId)
    {
        $this->refreshOnOpen[] = $componentId;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data, $keyId = 0, $keyParentId = 2, $keyIsOpen = 3)
    {
        parent::setData($data);
        if (empty($this->getData())){
            return $this;
        }
        $this->dataTree = new TreeDataStructure($keyId, $keyParentId, $keyIsOpen, $this->getData(), $this->limitLevel);
        return $this;
    }
    
    public function setLimitToLevel(int $limitToLevel)
    {
        $this->limitLevel = $limitToLevel;
        if (!empty($this->dataTree)) {
            $this->dataTree->setLimitToLevel($limitToLevel);
        }
    }
}
