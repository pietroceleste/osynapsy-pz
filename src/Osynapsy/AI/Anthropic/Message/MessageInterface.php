<?php
namespace Osynapsy\AI\Anthropic\Message;

interface MessageInterface
{
    public function get() : array;
    
    public function getJson() : string;
}
