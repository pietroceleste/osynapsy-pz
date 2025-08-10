<?php
namespace Osynapsy\AI\OpenAI\Model;

use Osynapsy\AI\OpenAI\Prompt\PromptInterface;

class Whisper_1 implements ModelInterface
{
    public function getId() : string
    {
        return 'whisper-1';
    }

    public function getEndpoint() : string
    {
        return 'https://api.openai.com/v1/audio/transcriptions';
    }
    
    public function buildRequest(PromptInterface $prompt) : array
    {
        $body = $prompt->get();
        $body['model'] = $this->getId();        
        return $body;
    }
    
    public function useJson(): bool
    {
        return false; // perché serve multipart/form-data
    }
    
    public function getResponse($rawresponse)
    {
        $response = json_decode($rawresponse, true);
        return $response['text'];
    }
}