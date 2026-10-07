<?php
namespace Osynapsy\AI\Ollama\Prompt;

class PromptGenerate implements PromptInterface
{
    protected string $system = '';
    protected string $prompt = '';
    protected array $images = [];

    public function __construct(string $prompt = '', array $images = [])
    {
        $this->prompt = $prompt;
        $this->images = $images;
    }

    /**
     * Imposta o accoda il testo del prompt principale
     */
    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Imposta un prompt di sistema facoltativo
     */
    public function setSystem(string $system): self
    {
        $this->system = $system;
        return $this;
    }

    /**
     * Aggiunge un'immagine in base64 (o un array di immagini base64)
     */
    public function addImage(string|array $imageBase64): self
    {
        if (is_array($imageBase64)) {
            $this->images = array_merge($this->images, $imageBase64);
        } else {
            $this->images[] = $imageBase64;
        }

        return $this;
    }

    /**
     * Restituisce il testo del prompt combinato
     */
    public function get(): string
    {
        if (empty($this->system)) {
            return $this->prompt;
        }

        return "System: {$this->system}" . PHP_EOL . "User: {$this->prompt}";
    }

    public function hasImages()
    {
        return !empty($this->images);
    }

    public function getArray()
    {
        $data = [
            'prompt' => $this->prompt
        ];

        if (!empty($this->system)) {
            $data['system'] = $this->system;
        }

        if (!empty($this->images)) {
            $data['images'] = array_values($this->images);
        }
        return $data;
    }

    /**
     * Restituisce l'array di immagini aggregate
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Genera il payload JSON per l'endpoint /api/generate
     */
    public function getJson(): string
    {
        return json_encode($this->getArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Restituisce i dati formattati come array per la richiesta cURL
     */
    public function toArray(): array
    {
        $data = [
            'prompt' => $this->prompt
        ];

        if (!empty($this->system)) {
            $data['system'] = $this->system;
        }

        if (!empty($this->images)) {
            $data['images'] = array_values($this->images);
        }

        return $data;
    }

    public function __toString(): string
    {
        return $this->get();
    }
}
