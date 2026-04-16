<?php
namespace Osynapsy\Robotics\Pudu\Command\Robot;

/**
 * Description of LiftingTask
 *
 * @author Pietro Celeste <p.celeste@qanda.cc>
 */
class LiftingTask implements PuduCommandInterface 
{
    private $sn;
    private $mapName;
    private $pointName;
    private $pointAttr; // LIFT_POINT, STAY_POINT, etc.

    public function __construct($sn, $mapName, $pointName, $pointAttr = 'LIFT_POINT') 
    {
        $this->sn = $sn;
        $this->mapName = $mapName;
        $this->pointName = $pointName;
        $this->pointAttr = $pointAttr;
    }

    public function getMethod(): string 
    { 
        return 'POST';         
    }

    public function getEndpoint(): string 
    { 
        return '/pudu-entry/open-platform-service/v1/lifting_task'; 
    }

    public function getQueryParams(): array
    { 
        return [];        
    }

    public function getBody(): array {
        return [
            "sn" => $this->sn,
            "payload" => [
                "tasks" => [[
                    "type" => "DEFAULT",
                    "point_list" => [[
                        "point_name" => $this->pointName,
                        "point_type" => ($this->pointName === 'LIFT_IN_PLACE' ? 'LIFT_IN_PLACE' : 'POINT'),
                        "point_attr" => $this->pointAttr,
                        "map_info" => ["map_name" => $this->mapName]
                    ]]
                ]]
            ]
        ];
    }
}
