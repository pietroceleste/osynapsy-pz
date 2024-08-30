<?php
namespace Osynapsy\AI\Anthropic;

use Osynapsy\Network\Rest;
use Osynapsy\AI\Anthropic\Model\ModelInterface;
use Osynapsy\AI\Anthropic\Message\Message;
use Osynapsy\AI\Anthropic\Message\MessageInterface;

class Client
{    
    const ANTHROPIC_API_DEFAULT_VERSION = '2023-06-01';
    const ANTHROPIC_API_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    
    protected $version;
    protected $model;
    protected $key;
    protected $cache = False;
    
    public function __construct(string $key, ?ModelInterface $model = null, ?string $version = null)
    {
        $this->key = $key;        
        $this->version = $version ?: self::ANTHROPIC_API_DEFAULT_VERSION;        
        $this->model = $model ?? new Model\Claude_3_Haiku();
    }
    
    public function getModel() : ModelInterface
    {
        return $this->model;
    }
    
    public function send(Message $prompt, $maxTokens = 1024)
    {        
        $headers = $this->headerRequestFactory();
        $body = $this->bodyRequestFactory($prompt, $maxTokens);        
        $response = Rest::postJson(self::ANTHROPIC_API_ENDPOINT, $body, $headers);
        return $response['body'];
    }
    
    protected function headerRequestFactory()
    {
        $headers = [
            'content-type' => 'application/json',            
            'anthropic-version' => $this->version,
            'x-api-key' => $this->key
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
