<?php
namespace Osynapsy\Robotics\Pudu\Command;

/**
 * Description of GetShops
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetShops implements CommandInterface
{
    public function getMethod(): string
    {
        return CommandInterface::METHOD_GET;
    }

    public function getEndpoint(): string
    {
        return '/pudu-entry/data-open-platform-service/v1/api/shop';
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function getBody(): array
    {
        return [];
    }
}
