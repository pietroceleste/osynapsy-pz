<?php
namespace Osynapsy\Robotics\Pudu\Command;

/**
 * Description of GetRobots
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class GetRobots implements CommandInterface
{
    public function getMethod(): string 
    { 
        return CommandInterface::METHOD_GET;        
    }

    public function getEndpoint(): string 
    { 
        return '/pudu-entry/robot-service/v1/open/robots'; 
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
