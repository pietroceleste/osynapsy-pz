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

    public static function post($url, $data, array $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return [
            'content-type' => $contentType,
            'header' => substr($response, 0, $headerSize),
            'body' => substr($response, $headerSize),
            'raw' => $response
        ];
    }

    public static function postJson($url, $data, $headers = [])
    {
        $json = json_encode($data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
        $response = self::post($url, $json, $headers + [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ]);
        $response['body'] = json_decode($response['body'], true) ?? $response['body'];
        return $response;
    }
}
