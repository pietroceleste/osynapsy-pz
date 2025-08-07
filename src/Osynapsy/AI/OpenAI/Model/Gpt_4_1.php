<?php
namespace Osynapsy\AI\OpenAI\Model;

class Gpt_4_1 implements ModelInterface
{
    public function getId() : string
    {
        return 'gpt-4.1';
    }
}
