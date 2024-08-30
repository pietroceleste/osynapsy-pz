<?php
namespace Osynapsy\AI\Anthropic\Message;

class Message implements MessageInterface
{
    protected $messages = [];
    
    public function add(string $role, string $content)
    {        
        $this->messages[] = ['role' => $role, 'content' => [$this->contentFactory($content)]];
        return $this;
    }        
    
    public function append(array|string $content)
    {
        $i = count($this->messages) - 1;
        $this->messages[$i]['content'][] = $this->contentFactory($content);
    }
    
    public function prepend(array|string $content)
    {
        $i = count($this->messages) - 1;
        array_unshift($this->messages[$i]['content'], $this->contentFactory($content));
    }
    
    protected function contentFactory(string|array $raw)
    {
        $type = is_array($raw) ? 'text' : 'text';
        return [
            'type' => 'text', 
            $type => is_array($raw) ? json_encode($raw) : $raw
        ];
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
