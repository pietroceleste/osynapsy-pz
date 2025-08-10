<?php
namespace Osynapsy\AI\OpenAI\Model;

use Osynapsy\AI\OpenAI\Prompt\PromptInterface;

class Gpt_4o implements ModelInterface
{
    public function getId() : string
    {
        return 'gpt-4o';
    }
    
    public function getEndpoint(): string 
    {
        return 'https://api.openai.com/v1/chat/completions';
    }
    
    public function buildRequest(PromptInterface $prompt) : array
    {
        $body = [
            'model' => $this->getId(),
            'messages' => $prompt->get()
        ];
        if (!empty($maxTokens)) {
            //$body['max_tokens'] = $maxTokens;
        }
        return $body;
    }
    
    public function useJson(): bool
    {
        return true;
    }
    
    public function getResponse($rawresponse)
    {
        return $rawresponse;
    }
}
