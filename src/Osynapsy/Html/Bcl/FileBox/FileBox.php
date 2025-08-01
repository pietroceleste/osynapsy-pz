<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Html\Bcl\FileBox;

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component;
use Osynapsy\Html\Ocl\InputBox;
use Osynapsy\Html\Ocl\HiddenBox;
use Osynapsy\Html\Bcl\Button;
use Osynapsy\Html\Bcl\LabelBox;
use Osynapsy\Html\Bcl\Link;

class FileBox extends Component
{    
    public $showPreview = true;    
    public $name;
    protected $fileBox;
    protected $uploadDir = '/upload/';
    protected $uploadActionLabel = 'uploadFile';
    protected $prefix;
    protected $postfix;
    
    
    public function __construct($name, $postfix = false)
    {        
        $this->name = $name;
        $this->postfix = $postfix;
        $this->requireJs('Bcl/FileBox/script.js');
        parent::__construct('div', $name.'cnt');                
    }        
    
    protected function __build_extra__()
    {        
        $filepath = $_REQUEST['__'.$this->name] ?? null;
        $div = $this->add(new Tag('div', null, 'input-group'));
        $div->add(new HiddenBox('__'.$this->name))->setDefaultValue($_REQUEST['__'.$this->name] ?? null);
        $div->add($this->fieldFilePathFactory($filepath));        
        $div->add($this->buttonGroupPrefixFactory());
        $div->add('<input type="text" class="form-control" readonly>');
        if (!empty($this->postfix)) {
            $div->add($this->buttonGroupPostfixFactory($this->postfix));
        }
        if (empty($filepath) || !$this->showPreview) {
            return;
        }       
        $span = $this->add(new Tag('span', $this->name.'_preview'));        
        $mimeType = mime_content_type($_SERVER['DOCUMENT_ROOT'] . $filepath);
        switch (true) {
            case str_starts_with($mimeType, 'image/'):
                $span->add($this->imagePreviewFactory($filepath));
            default:
                $span->add($this->genericDownloadFactory($filepath, $mimetype));
                break;            
        }
                
    }
    
    protected function fieldFilePathFactory($filepath)
    {
        return (new HiddenBox('__'.$this->name))->setDefaultValue($filepath);
    }
    
    protected function buttonGroupPrefixFactory()
    {
        $ButtonGroup = new Tag('span', null,  'input-group-btn');
        $ButtonGroup->add($this->buttonPrefixFactory());        
        return $ButtonGroup;
    }
    
    protected function buttonPrefixFactory()
    {        
        $Button = new Tag('span', null, 'btn btn-primary btn-file');
        $Button->add($this->fileInputFactory());
        $Button->add('<span class="fa fa-folder-open"></span>');
        return $Button;
    }
    
    protected function fileInputFactory()
    {
        $this->fileBox = new InputBox('file', $this->name);
        $this->fileBox->setAction(
            $this->uploadActionLabel, 
            implode(',', [$this->name, $this->uploadDir]), 
            'change-execute'
        );
        return $this->fileBox;
    }
    
    protected function buttonGroupPostfixFactory($postfix)
    {
        $ButtonGroup = new Tag('span', null, 'input-group-btn');
        $ButtonGroup->add($postfix);
        return $ButtonGroup;
    }        
    
    protected function imagePreviewFactory($filepath)
    {
        return (new Tag('img'))->att(['src' => $filepath, 'style' => 'width: 100%']);
    }
    
    protected function genericDownloadFactory($filepath, $mimetype)
    {
        $pathinfo = pathinfo($filepath);            
        $filename = $pathinfo['filename'].(!empty($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '');        
        $Label = new LabelBox('donwload_'.$this->name);
        $Label->att('style','padding: 10px; background-color: #ddd; margin-bottom: 10px;'.$mimetype);
        $Label->setLabel((new Link(false, $filepath, $filename.' <span class="fa fa-download"></span>'))->att('target', '_blank'));
        return $Label;
    }
    
    public function setUploadDirectory($uploadDirectoryPath)
    {
        $this->uploadDir = $uploadDirectoryPath;
        return $this;
    }
    
    public function enableCameraCapture()
    {
        $Button = new Button('btnCamera', 'button', 'fa fa-camera hidden-lg');
        $Button->att('onclick', "$('input[type=file]').attr('capture', 'camera').attr('accept','image/*').click();");
        $this->setPostfix($Button);
    }        
    
    public function showPreview($show = true)
    {
        $this->showPreview = $show;
    }
    
    protected function setPostfix($postfix)
    {
        $this->postfix = $postfix;
    }
    
    protected function setUploadDir($uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }
}