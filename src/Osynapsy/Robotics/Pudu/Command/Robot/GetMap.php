<?php
namespace Osynapsy\Robotics\Pudu\Command\Robot;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

/**
 * Description of GetMap
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetMap implements CommandInterface
{
    protected $shop_id;
    protected $map_name;

    public function __construct($mapName, $shopId)
    {
        $this->shop_id = $shopId;
        $this->map_name = $mapName;
    }

    public function getMethod(): string
    {
        return CommandInterface::METHOD_GET;
    }

    public function getEndpoint(): string
    {
        return '/pudu-entry/map-service/v1/open/map';
    }

    public function getQueryParams(): array
    {
        return ['map_name' => $this->map_name, 'shop_id' => $this->shop_id];
    }

    public function getBody(): array
    {
        return [];
    }
}
