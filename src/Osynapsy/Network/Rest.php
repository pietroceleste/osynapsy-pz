<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Network;

class Rest
{
    public static function get($endpoint, $data = [])
    {
        $url = $endpoint;
        if (!empty($data)) {
           $url .= '?'.http_build_query($data);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	return curl_exec($ch);
    }

    public static function post($url, $data, array $header = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = self::responseFactory($ch, curl_exec($ch));
        curl_close($ch);
        return $response;
    }

    protected static function responseFactory($ch, $rawResponse)
    {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response = [
            'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'content-type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
            'error' => curl_errno($ch),
            'header' => trim(substr($rawResponse, 0, $headerSize)),
            'body' => curl_errno($ch) ? curl_error($ch) : substr($rawResponse, $headerSize)
        ];
        return $response;
    }

    public static function postJson($url, $data, array $arrayHeaders = [])
    {
        $json = json_encode($data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
        $arrayHeaders['Content-Type'] = 'application/json';
        $arrayHeaders['Content-Length'] = strlen($json);
        $arrayHeaders['Expect'] = '';
        $response = self::post($url, $json, self::array2header($arrayHeaders));
        $response['body'] = json_decode($response['body'], true) ?? $response['body'];
        return $response;
    }

    private static function array2header(array $array = [])
    {
        return array_map(
            fn($key, $value) => sprintf('%s: %s', strtolower($key), $value),
            array_keys($array),
            $array
        );
    }
}
