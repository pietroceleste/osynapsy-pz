<?php
namespace Osynapsy\AI\Ollama\Prompt;

class Prompt implements PromptInterface
{
    protected array $messages = [];

    public function add(string $role, string $content): self
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $content
        ];

        return $this;
    }

    public function get()
    {
        $prompt = [];
        foreach($this->messages as $message) {
            $prompt[] = $message['content'];
        }
        return implode(PHP_EOL, $prompt);
    }

    public function getJson(): string
    {
        return json_encode($this->messages, JSON_UNESCAPED_UNICODE);
    }
}
