<?php
namespace Osynapsy\AI\OpenAI;

use Osynapsy\Network\Rest;
use Osynapsy\AI\OpenAI\Model\ModelInterface;
use Osynapsy\AI\OpenAI\Prompt\Prompt;
use Osynapsy\AI\OpenAI\Prompt\PromptInterface;

class Client
{
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

    public function send(PromptInterface $prompt, $maxTokens = 1024)
    {
        $body = $this->getModel()->buildRequest($prompt, $maxTokens);
        $postMethod = $this->getModel()->useJson() ? 'postJson' : 'post';
        $response = Rest::{$postMethod}($this->getModel()->getEndpoint(), $body, [], $this->key);
        return $this->getModel()->getResponse($response['body']);
    }

    public function promptFactory() : promptInterface
    {
        return new Prompt;
    }

    protected function setVersion(string $ver) : void
    {
        $this->version = $ver;
    }

    public function enableCache()
    {
        $this->cache = False;
    }
}
