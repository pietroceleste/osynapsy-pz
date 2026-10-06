<?php
namespace Osynapsy\AI\Ollama\Model;

use Osynapsy\AI\Ollama\Prompt\PromptInterface;
use Osynapsy\AI\Ollama\Response\ChatResponse;
use Osynapsy\AI\Ollama\Response\ResponseInterface;

/**
 * Description of CustomModel
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class CustomModel implements ModelInterface
{
    protected $id;
    protected $options = [
        'format' => null,
        'images' => [],
        'max-tokens' => null,
        'temperature' => null
    ];
    protected $images = [];

    public function __construct(string $id)
    {
        $arr = explode(':', $id, 2);
        $this->id = $arr[0];
        $this->tag = $arr[1] ?? null;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getEndpoint() : string
    {
        return '/api/generate';
    }

    public function buildRequest(PromptInterface $prompt) : array
    {
        $model = implode(':', [$this->getId(), $this->tag ?? 'latest']);
        $body = [
            'model' => $model,
            'prompt' => $prompt->get(),
            'stream' => false
        ];
        foreach($this->options as $option => $value) {
            if (!is_null($value)) {
                $body[$option] = $value;
            }
        }
        return $body;
    }

    public function useJson(): bool
    {
        return true;
    }

    public function getResponse(array $rawresponse) : ResponseInterface
    {
        return new ChatResponse($rawresponse);
    }

    public function setFormat(string $format)
    {
        $this->option['format'] = $format;
    }

    public function setTemperature(float $v)
    {
        $this->option['temperature'] = $v;
    }

    public function addImages(array $images)
    {
        $this->option['images'] = array_merge($this->option['images'], $images);
    }
}
