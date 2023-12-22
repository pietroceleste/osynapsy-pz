<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl;

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;
use Osynapsy\Html\Bcl\ContextMenu;
use Osynapsy\Html\Bcl\Link;

class Gallery extends Component
{
    protected $photos;
    protected $dim = array(180,180);
    protected $actions;
    protected $defaultPhoto;
    protected $contextMenu;
    protected $addImageUrlAction;

    public function __construct($id, $dim = array(180,180))
    {
        parent::__construct('div', $id);
        $this->add(new Tag('span', null, 'gallery'));
        $this->dim = $dim;
    }

    public function addAction($label, $action, $actionField='id')
    {
        if (!$this->contextMenu) {
            $this->contextMenu = new ContextMenu($this->id.'ContextMenu');
        }
        $this->contextMenu->addAction($label, $action, $actionField);
        $this->actions[] = array($label, $action, $actionField);
    }

    public function addImageUrl($url)
    {
        $this->addImageUrlAction = $url;
    }

    public function __build_extra__()
    {
        if (!empty($this->addImageUrlAction)) {
            $this->photos[] = $this->thumbnailPlusFactory();
        }
        foreach ($this->photos as $photo) {
            $this->add(is_object($photo) ? $photo : $this->thumbnailFactory($photo));
        }
        if ($this->contextMenu) {
            $this->add($this->contextMenu);
        }
    }

    protected function thumbnailPlusFactory()
    {
        $div = new Tag('div', null, 'img-thumbnail text-center');
        $div->att('style', sprintf('width: %spx; height: %spx', $this->dim[0], $this->dim[1]));
        $Link = $div->add(new Link('btnAdd', $this->addImageUrlAction , '<span class="fa fa-plus fa-2x" style="margin-top: 45%;"></span>', 'media'));
        $Link->openInModal('Gallery image');
        return $div;
    }


    protected function thumbnailFactory($photo)
    {
        $div = $this->add(new Tag('div', null, 'col-xs-2 col-md-1'));
        $a = $div->add(new Tag('a', null, 'thumbnail'));
        if ($this->contextMenu) {
            $a->att('class','BclContextMenuOrigin',true)
              ->att('data-bclcontextmenuid', $this->id.'ContextMenu');
        }
        if ($this->defaultPhoto && $this->defaultPhoto[0] == $photo[$this->defaultPhoto[1]]) {
            $a->att('style','border-color: red;');
        }
        $a->add($this->imageFactory($photo));
        //Create Photo caption
        $div->add($this->captionFactory($a, $photo));
    }

    protected function captionFactory($a, $photo)
    {
        $caption = new Tag('div', null, 'caption text-center');
        if ($photo['label']) {
            $caption->add($photo['label']);
        }
        //If delete action is set add button delete
        if (empty($this->actions)) {
            return $caption;
        }
        foreach ($this->actions as $action) {
            $caption->add(new Tag('button', null, 'btn btn-danger cmd-execute'))
                    ->att(['type' => 'button', 'data-action' => $action[1], 'data-action-param' => $photo[$action[2]]])
                    ->add($action[0]);
            $a->att('data-action-param' , $photo[$action[2]]);
        }
        return $caption;
    }

    protected function imageFactory($photo)
    {
        $image = new Tag('img');
        $image->att('src', $photo['url']);
        if (!empty($this->dim[0])) {
            $image->att('style','width: '.$this->dim[0].'px');
        }
        if (!empty($image->dim[1])) {
            $image->att('style','height: '.$this->dim[1].'px');
        }
        return $image;
    }

    public function setPhotoList($list)
    {
        $this->photos = $list;
    }

    public function setDefault($val,$field='id')
    {
        $this->defaultPhoto = array($val,$field);
    }

    public function setThumbnailDimension($width, $height)
    {
        $this->dim = [$width, $height];
    }
}
