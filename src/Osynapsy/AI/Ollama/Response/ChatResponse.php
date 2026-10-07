<?php
namespace Osynapsy\AI\Ollama\Response;

class ChatResponse implements ResponseInterface
{
    protected array $raw;
    protected ?string $error = null;

    public function __construct(array $response)
    {
        $this->raw = $response;
        // Ollama solitamente restituisce l'errore come stringa in 'error'
        $this->error = $response['error'] ?? null;
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
     * Contenuto testuale della risposta
     * Supporta sia /api/generate che /api/chat
     */
    public function getContent(): string
    {
        // Se usi l'endpoint /api/chat
        if (isset($this->raw['message']['content'])) {
            return $this->raw['message']['content'];
        }

        // Se usi l'endpoint /api/generate
        if (isset($this->raw['response'])) {
            return $this->raw['response'];
        }

        return '';
    }

    /**
     * Calcola i Token al secondo (Tokens per Second)
     * Basato su eval_count (token generati) e eval_duration (tempo in nanosecondi)
     */
    public function getTokensPerSecond(): float
    {
        $count = $this->raw['eval_count'] ?? 0;
        $durationNs = $this->raw['eval_duration'] ?? 0;

        if ($count === 0 || $durationNs === 0) {
            return 0.0;
        }

        // Convertiamo nanosecondi in secondi: ns / 1.000.000.000
        $durationSec = $durationNs / 1e9;

        return round($count / $durationSec, 2);
    }

    /**
     * Ritorna il numero totale di token (prompt + generazione)
     */
    public function getTotalTokens(): int
    {
        return ($this->raw['prompt_eval_count'] ?? 0) + ($this->raw['eval_count'] ?? 0);
    }

    public function getRaw(): array
    {
        return $this->raw;
    }
}