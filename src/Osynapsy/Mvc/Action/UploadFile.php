<?php
namespace Osynapsy\Mvc\Action;

use Osynapsy\Mvc\Action\AbstractAction;
use Osynapsy\Network\UploadManager;

/**
 * Description of SavePosition
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class UploadFile extends AbstractAction
{       
    protected $fieldName;    
    protected $uploadDir;
    
    public function execute($fieldName, $uploadDir)
    {
        try {            
            $this->fieldName = $fieldName;
            $this->uploadDir = $uploadDir;            
            $this->beforeSave();
            $fileName = $this->savePictures($this->fieldName, $this->uploadDir);            
            $this->afterSave($fileName);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    
    protected function savePictures($fieldId, $uploadDirectory)
    {        
        return (new UploadManager())->saveFile($fieldId, $uploadDirectory);
    }   
    
    protected function beforeSave()
    {        
    }
    
    protected function afterSave($fileName)
    {        
        $this->getController()->js(sprintf("$('#__%s').val('%s');", $this->fieldName, $fileName));
        $this->getController()->refreshComponents([$this->fieldName . 'cnt']);
    }
}
