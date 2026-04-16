<?php
namespace Osynapsy\Robotics\Pudu\Command;

/**
 * Description of HealthCheck
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class HealthCheck implements CommandInterface
{
    protected array $params;
    
    public function __construct(array $params = ['a' => '1', 'b' => '2', 'c' => '3']) 
    {
        $this->params = $params;        
    }
    
    public function getMethod(): string 
    { 
        return CommandInterface::METHOD_GET;        
    }

    public function getEndpoint(): string 
    { 
        return '/pudu-entry/data-open-platform-service/v1/api/healthCheck'; 
    }

    public function getQueryParams(): array
    { 
        return $this->params;
    }
    
    public function getBody(): array
    {
        return [];
    }
}
