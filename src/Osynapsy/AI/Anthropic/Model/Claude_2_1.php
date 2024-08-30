<?php
namespace Osynapsy\AI\Anthropic\Model;

class Claude_2_1 implements ModelInterface
{
    public function getId() : string
    {
        return 'claude-2.1';
    }
}