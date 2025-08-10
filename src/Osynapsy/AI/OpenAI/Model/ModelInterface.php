<?php
namespace Osynapsy\AI\OpenAI\Model;

use Osynapsy\AI\OpenAI\Prompt\PromptInterface;

interface ModelInterface
{
    public function getId() : string;
    
    public function getEndpoint() : string;
    
    public function buildRequest(PromptInterface $prompt) : array;
}
