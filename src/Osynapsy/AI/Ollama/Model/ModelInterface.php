<?php
namespace Osynapsy\AI\Ollama\Model;

use Osynapsy\AI\Ollama\Prompt\PromptInterface;

interface ModelInterface
{
    public function getId() : string;
    
    public function getEndpoint() : string;
    
    public function buildRequest(PromptInterface $prompt) : array;
}
