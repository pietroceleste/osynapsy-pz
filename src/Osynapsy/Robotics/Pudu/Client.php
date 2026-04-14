<?php
/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Robotics\Pudu;

/**
 * Pudu Robotics API Client
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Client
{
    CONST EP_MAP = '/map-service/v1/open/current';

    private $key;
    private $secret;
    private $baseUrl;

    public function __construct($key, $secret, $baseUrl)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Esegue una chiamata GET
     */
    public function get($endpoint, array $queryParams = [])
    {
        return $this->request('GET', $endpoint, $queryParams);
    }

    /**
     * Esegue una chiamata POST
     */
    public function post($endpoint, array $body = [], array $queryParams = [])
    {
        return $this->request('POST', $endpoint, $queryParams, $body);
    }

    /**
     * Cuore del modulo: gestisce firma e invio
     */
    protected function request($rawmethod, $endpoint, array $queryParams = [], array $body = [])
    {
        $method = strtoupper($rawmethod);
        $gmtDate = gmdate("D, d M Y H:i:s") . " GMT";
        $contentType = "application/json";
        $accept = "application/json";

        // 1. Costruzione Query String ordinata (necessaria per la firma)
        $encodedParams = array_map([$this, 'customEncode'], $queryParams);
        ksort($encodedParams);

        $queryString = '';
        if (!empty($encodedParams)) {
            $pairs = [];
            foreach ($encodedParams as $k => $v) {
                $pairs[] = "$k=$v";
            }
            $queryString = "?" . implode("&", $pairs);
        }

        // 2. Pulizia Path per la firma (rimozione prefissi ambiente)
        $pathForSignature = $this->getCleanPath($endpoint);
        if (!empty($queryString)) {
            $pathForSignature .= $queryString;
        }

        // 3. Calcolo MD5 per il body (solo per POST)
        $contentMd5 = "";
        $bodyJson = "";
        if ($method === 'POST' && !empty($body)) {
            $bodyJson = json_encode($body);
            $contentMd5 = base64_encode(md5($bodyJson));
        }

        // 4. Generazione Firma HMAC-SHA1
        $signingStr = "x-date: $gmtDate\n$method\n$accept\n$contentType\n$contentMd5\n$pathForSignature";
        $signature = base64_encode(hash_hmac('sha1', $signingStr, $this->secret, true));

        $authorization = sprintf(
            'hmac id="%s", algorithm="hmac-sha1", headers="x-date", signature="%s"',
            $this->key,
            $signature
        );

        // 5. Esecuzione tramite cURL (o il tuo modulo Osynapsy\Network\Rest)
        return $this->execute($method, $this->baseUrl . $endpoint . $queryString, [
            "Accept: $accept",
            "Content-Type: $contentType",
            "x-date: $gmtDate",
            "Authorization: $authorization"
        ], $bodyJson);
    }

    private function getCleanPath($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $prefixes = ['/release', '/test', '/prepub'];
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return substr($path, strlen($prefix)) ?: '/';
            }
        }
        return $path ?: '/';
    }

    private function customEncode($str)
    {
        $map = ['%' => '%25', '#' => '%23', '=' => '%3D', '&' => '%26', '?' => '%3F', '/' => '%2F', '+' => '%2B', ' ' => '%20'];
        return str_replace(array_keys($map), array_values($map), $str);
    }

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
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Pudu API Error: " . $error);
        }

        return json_decode($response, true);
    }

    public function getMapCurrent($sn)
    {
        $endpoint = $this->baseUrl . self::EP_MAP;
        $params = ['sn' => $sn, 'need_element' => 'true'];
        return $this->get($endpoint, $params);
    }
}