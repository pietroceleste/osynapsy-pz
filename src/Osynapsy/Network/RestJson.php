<?php
namespace Osynapsy\Network;

/**
 * Description of RestJson
 *
 * @author peter
 */
class RestJson
{
    public static function auth($host, $baseUrl, $user, $pass)
    {
        $header[] = 'Content-Type: application/json';
        $userData = [];
        $userData['username'] = $user;
        $userData['password'] = $pass;
        $userData['rememberMe'] = true;

        $body = json_encode($userData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

        $header = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body)
        );

        $url = $host . $baseUrl . '/authenticate';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $authObject = json_decode(curl_exec($ch));

        return $authObject->id_token;
    }



    public static function get($endpoint, $token)
    {
        $baseUrl = 'https://app.enolo.it/api';

        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        );

        $url = $baseUrl . $endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        return curl_exec($ch);
    }
}
