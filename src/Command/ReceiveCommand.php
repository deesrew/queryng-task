<?php

namespace App\Command;

use App\Repository\TaskRepository;
use App\Service\RabbitMQ;
use ErrorException;
use Exception;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class ReceiveCommand extends Command
{

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:receive')
            ->setDescription('Receive queue.')
            ->setHelp('This command allows you to receive queue')
        ;
    }

    /**
     * @throws ErrorException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rabbit = new RabbitMQ();
        $rabbit->setConnection();
        $rabbit->setChanel();

        $callback = function ($msg) {

            $data = json_decode($msg->body, true);

            $dataArr = [
                'field1' => $data['field1'],
                'field2' => $data['field2'],
                'date_created' => $data['date_created']
            ];

            $this->taskRepository->insertOne($dataArr);

        };

        $rabbit->queueDeclare('queueForCron');
        $rabbit->consume('queueForCron', $callback);

        $rabbit->close();

        return true;
    }

}