<?php
namespace Osynapsy\AI\OpenAI\Prompt;

class File implements PromptInterface
{    
    protected $file; 
    
    public function __construct($filename)
    {
        $this->file = new \CURLFile($filename);
    }
    
    public function get() : array
    {
        return ['file' => $this->file];
    }
    
    public function getJson() : string
    {
        return json_decode($this->get());
    }
}
