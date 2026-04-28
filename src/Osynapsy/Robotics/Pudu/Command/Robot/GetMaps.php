<?php
namespace Osynapsy\Robotics\Pudu\Command\Robot;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

/**
 * Description of GetMaps
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetMaps implements CommandInterface
{
    protected $sn;
    
    public function __construct($shopId) 
    {
        $this->shop_id = $shopId;        
    }
    
    public function getMethod(): string 
    { 
        return CommandInterface::METHOD_GET;        
    }

    public function getEndpoint(): string 
    { 
        return '/pudu-entry/data-open-platform-service/v1/api/maps'; 
    }

    public function getQueryParams(): array
    { 
        return ['shop_id' => $this->shop_id];
    }
    
    public function getBody(): array
    {
        return [];
    }
}
