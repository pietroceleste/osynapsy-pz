<?php
namespace Osynapsy\Robotics\Pudu\Command\Robot;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

/**
 * Description of GetMapCurrent
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetMapCurrent implements CommandInterface
{
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
        return '/pudu-entry/map-service/v1/open/current'; 
    }

    public function getQueryParams(): array
    { 
        return ['sn' => $this->sn, 'need_element' => 'true'];
    }
    
    public function getBody(): array
    {
        return [];
    }
}
