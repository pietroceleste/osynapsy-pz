<?php
namespace Osynapsy\Robotics\Pudu\Command\Robot;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

/**
 * Description of GetStatus
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetStatus implements CommandInterface
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
        return '/pudu-entry/open-platform-service/v2/status/get_by_sn'; 
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
