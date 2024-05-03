<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Http;

/**
 * Abstract Response
 */
abstract class Response
{
    protected $headers = [];
    protected $streams = [];

    /**
     * Init response with the content type
     *
     * @param type $contentType
     */
    public function __construct($contentType = 'text/plain')
    {
        $this->setContentType($contentType);
    }

    /**
     * Method that add content to the response
     *
     * @param mixed $content
     * @param mixed $part
     * @param bool $checkUnique
     * @return mixed
     */
    public function writeStream($content, $id = 'main')
    {
        if (!array_key_exists($id, $this->streams)) {
            $this->streams[$id] = [];
        }
        $this->streams[$id][] = $content;
    }

    /**
     * Send header location to browser
     *
     * @param string $url
     */
    public function go($url)
    {
        header('Location: '.$url);
    }

    /**
     * Reset content part.
     *
     * @param mixed $part
     */
    public function resetStream($part = 'main')
    {
        $this->streams[$part] = [];
    }

    /**
     * Set content type of the response
     *
     * @param string $type
     */
    public function setContentType($type)
    {
        $this->setHeader('Content-Type', $type);
    }

    /**
     * Buffering of header
     *
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Set cookie
     *
     * @param string $valueId
     * @param string $value
     * @param unixdatetime $expiry
     */
    public static function cookie($valueId, $value, $expiry = null, $excludeThirdLevel = true)
    {
        if (headers_sent()) {
           return false;
        }
        $domain = filter_input(\INPUT_SERVER,'SERVER_NAME');
        if ($excludeThirdLevel) {
            $app = explode('.',$domain);
            if (count($app) == 3){
                $domain = ".".$app[1].".".$app[2];
            }
        }
        if (empty($expiry)) {
            $expiry = time() + (86400 * 365);
        }
        return setcookie($valueId, $value, $expiry, "/", $domain);
    }

    /**
     * Send header buffer
     */
    protected function sendHeader() : bool
    {
        if (headers_sent()) {
            return false;
        }
        foreach ($this->headers as $key => $value) {
           header(sprintf('%s: %s', $key, $value));
        }
        return true;
    }

    /**
     * Method for build response string
     * @abstract
     */
    abstract public function __toString();
}
