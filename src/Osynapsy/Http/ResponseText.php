<?php
namespace Osynapsy\Http;

/**
 * Description of ResponseText
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class ResponseText extends Response
{
    public function __construct($contentType = 'text/plain')
    {
        parent::__construct($contentType);
    }

    public function __toString()
    {
        $this->sendHeader();
        return implode(PHP_EOL, array_map(fn($stream) => is_array($stream) ? implode('',$stream) : $stream, $this->streams));
    }
}
