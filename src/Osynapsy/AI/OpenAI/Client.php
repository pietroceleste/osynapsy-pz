<?php
namespace Osynapsy\AI\OpenAI;

use Osynapsy\Network\Rest;
use Osynapsy\AI\OpenAI\Model\ModelInterface;
use Osynapsy\AI\OpenAI\Message\Message;
use Osynapsy\AI\OpenAI\Message\MessageInterface;

class Client
{    
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    
    protected $version;
    protected $model;
    protected $key;
    protected $cache = False;
    
    public function __construct(string $key, ?ModelInterface $model = null)
    {
        $this->key = $key;        
        $this->model = $model ?? new Model\Gpt_4o_mini();
    }
    
    public function getModel() : ModelInterface
    {
        return $this->model;
    }
    
    public function send(Message $prompt, $maxTokens = 1024)
    {        
        $headers = $this->headerRequestFactory();
        $body = $this->bodyRequestFactory($prompt, $maxTokens);        
        $response = Rest::postJson(self::API_ENDPOINT, $body, $headers);
        return $response['body'];
    }
    
    protected function headerRequestFactory()
    {
        $headers = [
            'content-type' => 'application/json',            
            'anthropic-version' => $this->version,
            'Authorization' =>  sprintf('Bearer %s', $this->key)
        ];        
        if (!empty($this->cache)) {
            $headers['anthropic-beta'] = 'prompt-caching-2024-07-31';
        }
        return $headers;
    }
    
    protected function bodyRequestFactory(Message $prompt, $maxTokens) : array
    {              
        $body = [
            'model' => $this->getModel()->getId(),            
            'messages' => $prompt->get()            
        ];
        if (!empty($maxTokens)) {
            $body['max_tokens'] = $maxTokens;
        }
        return $body;
    }
    
    public function messageFactory() : MessageInterface
    {
        return new Message;        
    }
    
    protected function setVersion(string $ver) : void
    {
        $this->version = $ver;
    }
    
    public function enableCache()
    {
        $this->cache = True;
    }
}
