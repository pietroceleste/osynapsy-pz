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
    
    public function __construct($serialNumber) 
    {
        $this->sn = $serialNumber;        
    }
    
    public function getMethod(): string 
    { 
        return CommandInterface::METHOD_GET;        
    }

    public function getEndpoint(): string 
    { 
        return '/pudu-entry/map-service/v1/open/list'; 
    }

    public function getQueryParams(): array
    { 
        return ['sn' => $this->sn];
    }
    
    public function getBody(): array
    {
        return [];
    }
}
