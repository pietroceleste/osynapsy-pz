<?php
namespace Osynapsy\AI\Ollama\Model;

use Osynapsy\AI\Ollama\Prompt\PromptInterface;
use Osynapsy\AI\Ollama\Response\ChatResponse;
use Osynapsy\AI\Ollama\Response\GenerateResponse;
use Osynapsy\AI\Ollama\Response\ResponseInterface;

/**
 * Description of CustomModel
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class CustomModel implements ModelInterface
{
    const ENDPOINT_CHAT = '/api/chat';
    const ENDPOINT_GENERATE = '/api/generate';

    protected $id;
    protected $repo = [
        'format' => null,
        'max-tokens' => null,
        'keep_alive' => -1,
        'options' => [
            'temperature' => 0.1
        ]
    ];
    
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
        return self::ENDPOINT_GENERATE;
    }

    public function buildRequest(PromptInterface $prompt) : array
    {
        $this->setParameter('model', implode(':', [$this->getId(), $this->tag ?? 'latest']));
        $this->setParameter('stream', false);
        $body = [];
        if ($this->getEndpoint() === self::ENDPOINT_GENERATE) {
            $body += $prompt->getArray();
        }
        foreach($this->repo as $parameter => $value) {
            if (!is_null($value)) {
                $body[$parameter] = $value;
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
        return $this->getEndpoint() === self::ENDPOINT_GENERATE
            ? new GenerateResponse($rawresponse)
            : new ChatResponse($rawresponse);
    }

    public function setFormat(string $format)
    {
        return $this->setParameter('format', $format);
    }

    public function setTemperature(float $v)
    {
        return $this->setOption('temperature', $v);
    }

    public function setThink(bool $v)
    {
        return $this->setParameter('think', $v);
    }

    public function setOption($option, $value)
    {
        $this->repo['options'][$option] = $value;
        return $this;
    }

    public function getOption($option)
    {
        return $this->repo['options'][$option];
    }

    public function setStop(array $stop)
    {
        return $this->setOption('stop', $stop);
    }

    public function getParameter($parameter)
    {
        return $this->repo[$parameter];
    }

    public function setParameter($parameter, $value)
    {
        $this->repo[$parameter] = $value;
        return $this;
    }
}
