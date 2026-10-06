<?php
namespace Osynapsy\AI\Ollama\Model;

class Qwen_2_5 extends CustomModel
{
    protected $tag;
    protected $format;

    public function __construct($tag = null)
    {
        $this->tag = $tag;
    }

    public function getId() : string
    {
        return 'qwen2.5';
    }
}
