<?php
namespace Osynapsy\Robotics\Pudu\Command;

/**
 * Description of CommandInterface
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
interface CommandInterface
{
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    
    public function getMethod(): string;
    public function getEndpoint(): string;
    public function getQueryParams(): array;
    public function getBody(): array;
}
