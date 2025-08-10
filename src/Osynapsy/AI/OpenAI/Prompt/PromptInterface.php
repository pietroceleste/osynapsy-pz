<?php
namespace Osynapsy\AI\OpenAI\Prompt;

interface PromptInterface
{
    public function get() : array;
}
