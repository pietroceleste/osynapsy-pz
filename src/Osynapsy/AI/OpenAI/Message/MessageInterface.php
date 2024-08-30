<?php
namespace Osynapsy\AI\OpenAI\Message;

interface MessageInterface
{
    public function get() : array;
    
    public function getJson() : string;
}
