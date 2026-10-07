<?php
namespace Osynapsy\AI\Ollama\Response;

class GenerateResponse implements ResponseInterface
{
    protected array $raw;
    protected ?string $error = null;

    public function __construct(array $response)
    {
        $this->raw = is_array($response) ? $response : ['response' => $response];
        $this->error =$response['error'] ?? null;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getErrorMessage(): string
    {
        return $this->error ?? '';
    }

    /**
     * Contenuto testuale grezzo restituito dall'endpoint /api/generate
     */
    public function getContent(): string
    {
        return $this->raw['response'] ?? '';
    }

    /**
     * Decodifica il contenuto testuale e lo restituisce come array associativo PHP.
     * Utile quando si usa Ollama con "format" => "json".
     */
    public function getArray(): array
    {
        $content =$this->getContent();
        if (empty($content)) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ?$decoded : [];
    }

    /**
     * Pulisce eventuali marcatori Markdown (```json ... ```) se presenti e restituisce
     * la stringa JSON pura.
     */
    public function getJson(): string
    {
        $content = trim($this->getContent());

        // Rimuove eventuali blocchi markdown ```json ... ``` se il modello li include
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);
        }

        return trim($content);
    }

    /**
     * Calcola i Token al secondo (Tokens per Second)
     */
    public function getTokensPerSecond(): float
    {
        $count = $this->raw['eval_count'] ?? 0;
        $durationNs = $this->raw['eval_duration'] ?? 0;

        if ($count === 0 || $durationNs === 0) {
            return 0.0;
        }

        $durationSec = $durationNs / 1e9;

        return round($count / $durationSec, 2);
    }

    /**
     * Ritorna il numero di token usati per il Prompt/Immagini
     */
    public function getPromptTokens(): int
    {
        return $this->raw['prompt_eval_count'] ?? 0;
    }

    /**
     * Ritorna il numero di token generati nell'output
     */
    public function getCompletionTokens(): int
    {
        return $this->raw['eval_count'] ?? 0;
    }

    /**
     * Ritorna il numero totale di token (prompt + generazione)
     */
    public function getTotalTokens(): int
    {
        return $this->getPromptTokens() + $this->getCompletionTokens();
    }

    /**
     * Ritorna il motivo dell'interruzione della generazione (es: 'stop', 'length')
     */
    public function getDoneReason(): string
    {
        return $this->raw['done_reason'] ?? '';
    }

    public function getRaw(): array
    {
        return $this->raw;
    }
}
