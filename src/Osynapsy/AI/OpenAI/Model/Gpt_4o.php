<?php
namespace Osynapsy\AI\OpenAI\Model;

class Gpt_4o implements ModelInterface
{
    public function getId() : string
    {
        return 'gpt-4o';
    }
}
