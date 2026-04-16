<?php

namespace Osynapsy\Robotics\Pudu;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

/**
 * Pudu Robotics API Client
 * 
 * 
 * * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Client
{
    private $key;
    private $secret;
    private $baseUrl;

    public function __construct($key, $secret, $baseUrl)
    {
        $this->key = trim($key);
        $this->secret = trim($secret);
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function executeCommand(CommandInterface $command) 
    {
        return $this->request(
            $command->getMethod(),
            $command->getEndpoint(),
            $command->getQueryParams(),
            $command->getBody()
        );
    }
    
    /**
     * Cuore del modulo: Gestione Firma HMAC (Logica allineata a Python)
     */
    protected function request($method, $endpoint, array $queryParams = [], array $body = [])
    {
        $method = strtoupper($method);
        // Formato GMT: Tue, 14 Apr 2026 14:00:00 GMT
        $gmtDate = gmdate("D, d M Y H:i:s") . " GMT";
        $contentType = "application/json";
        $accept = "application/json";

        // 1. GESTIONE PATH E PARAMETRI PER LA FIRMA (RAW)
        ksort($queryParams);
        $sigParams = [];
        foreach ($queryParams as $k => $v) {
            if ($v === "" || $v === null) {
                $sigParams[] = $k;
            } else {
                // In caso di array, unisce con virgola come da specifica
                $val = is_array($v) ? implode(',', $v) : $v;
                $sigParams[] = $k . "=" . $val;
            }
        }

        $cleanPath = $this->getCleanPath($endpoint);
        $pathForSignature = $cleanPath;
        if (!empty($sigParams)) {
            $pathForSignature .= "?" . implode("&", $sigParams);
        }

        // 2. GESTIONE MD5 (Base64 dell'Hex Digest)
        $contentMd5 = "";
        $bodyJson = "";
        if ($method === 'POST') {
            $bodyJson = !empty($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : "{}";
            $contentMd5 = base64_encode(md5($bodyJson)); 
        }

        // 3. COSTRUZIONE STRINGA DI FIRMA (Struttura a 6 campi)
        $signingStr = sprintf(
            "x-date: %s\n%s\n%s\n%s\n%s\n%s",
            $gmtDate,
            $method,
            $accept,
            $contentType,
            $contentMd5,
            $pathForSignature
        );

        // 4. CALCOLO FIRMA HMAC-SHA1
        $signature = base64_encode(hash_hmac('sha1', $signingStr, $this->secret, true));

        $authorization = sprintf(
            'hmac id="%s", algorithm="hmac-sha1", headers="x-date", signature="%s"',
            $this->key,
            $signature
        );

        // 5. COSTRUZIONE URL FINALE (Codifica per invio HTTP)
        $urlParts = [];
        foreach ($queryParams as $k => $v) {
            $val = is_array($v) ? implode(',', array_map('rawurlencode', $v)) : rawurlencode($v);
            $urlParts[] = "$k=$val";
        }
        $finalUrl = $this->baseUrl . $endpoint . (!empty($urlParts) ? "?" . implode("&", $urlParts) : "");

        return $this->execute($method, $finalUrl, [
            "Host: " . parse_url($this->baseUrl, PHP_URL_HOST),
            "Accept: $accept",
            "Content-Type: $contentType",
            "x-date: $gmtDate",
            "Authorization: $authorization"
        ], $method === 'POST' ? $bodyJson : null);
    }

    /**
     * Pulizia del path dai prefissi ambiente
     */
    private function getCleanPath($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $prefixes = ['/release', '/test', '/prepub'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                // Rimuove il primo segmento (es. /release)
                $parts = explode('/', ltrim($path, '/'), 2);
                return isset($parts[1]) ? '/' . $parts[1] : '/';
            }
        }
        return $path ?: '/';
    }
    
    /**
     * Esecuzione cURL
     */
    protected function execute($method, $url, $headers, $body = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST' && $body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Pudu cURL Error: " . $error);
        }

        // Decodifica il JSON se possibile
        $decoded = json_decode($response, true);
        return ($decoded === null) ? $response : $decoded;
    }
}
