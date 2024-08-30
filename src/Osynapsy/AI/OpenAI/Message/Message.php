<?php
namespace Osynapsy\AI\OpenAI\Message;

class Message implements MessageInterface
{
    protected $messages = [];
    
    public function add(string $role, string $content)
    {        
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }        
    
    public function get() : array
    {
        return $this->messages;
    }
    
    public function getJson() : string
    {
        return json_decode($this->messages);
    }
}
