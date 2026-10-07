<?php
namespace Osynapsy\AI\Ollama\Response;

interface ResponseInterface
{
    public function hasError(): bool;

    public function getErrorMessage(): string;
    
    public function getContent(): string;
}
