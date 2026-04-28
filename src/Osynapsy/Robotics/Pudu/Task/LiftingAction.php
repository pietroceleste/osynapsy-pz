<?php
namespace Osynapsy\Robotics\Pudu\Task;

use Osynapsy\Robotics\Pudu\Command\CommandInterface;

class LiftingAction implements CommandInterface
{
    const ACTION_FINISH_ONE = 'FINISH_ONE';
    const ACTION_PAUSE = 'PAUSE';
    const ACTION_RESUME = 'RESUME';
    const ACTION_CANCEL_ALL = 'CANCEL_ALL_LIFTIONG';

    private $sn;
    private $action;
    private $taskId;

    /**
     * @param string $sn Seriale del robot
     * @param string $taskId id del task rilasciato dal cloud in fase di creazione
     * @param string $action azione da svolgere
     */
    public function __construct($sn, $taskId, $action)
    {
        $this->sn = $sn;
        $this->taskId = $taskId;
        $this->action = $action;
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getEndpoint(): string
    {
        return '/pudu-entry/open-platform-service/v1/lifting_action';
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function getBody(): array
    {
        return [
            "sn" => $this->sn,
            "task_id" => $this->taskId,
            "action" => $this->action
        ];
    }
}
