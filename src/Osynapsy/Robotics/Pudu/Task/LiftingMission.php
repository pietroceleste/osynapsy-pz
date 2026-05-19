<?php
namespace Osynapsy\Robotics\Pudu\Task;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

class LiftingMission implements CommandInterface
{
    const POINT_TYPE_LIFT = 'LIFT_POINT'; //Punto carico pedana
    const POINT_TYPE_STAY = 'STAY_POINT'; //Punto di transito
    const POINT_TYPE_DROP = 'DROP_POINT'; //Punto di consegna

    private $sn;
    private $points = [];
    private $mapName;

    /**
     * @param string $sn Seriale del robot
     * @param string $mapName Nome della mappa (es. "0#0#pp")
     */
    public function __construct($sn, $mapName = "0#0#pp")
    {
        $this->sn = $sn;
        $this->mapName = $mapName;
    }

    /**
     * Aggiunge un punto alla missione
     * * @param string $name Nome del punto (es. "1-1")
     * @param string $attr Attributo (pickup, stay, dropoff)
     * @param string $type Tipo (shelf, POINT)
     */
    public function addPoint($name, $attr, $type = 'POINT')
    {
        $this->points[] = [
            "point_name" => $name,
            "point_type" => $type,
            "point_attr" => $attr,
            "map_info"   => [ "map_name" => $this->mapName]
        ];
        return $this; // Consente il concatenamento (Fluent Interface)
    }

    public function addPoints(array $dataset)
    {
        foreach($dataset as $point) {
            $this->addPoint($point[0], $point[1] ?? self::POINT_TYPE_STAY);
        }
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

    public function getBody(): array
    {
        $body = [
            "sn" => $this->sn,
            "payload" => [
                "tasks" => [
                    [
                        "point_list" => $this->points
                    ]
                ],
                "type" => "DEFAULT"
            ]
        ];
        return $body;
    }
}
