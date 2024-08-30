<?php
namespace Osynapsy\AI\Anthropic\Model;

class Claude_3_Haiku implements ModelInterface
{
    public function getId() : string
    {
        return 'claude-3-haiku-20240307';
    }
}
