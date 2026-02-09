<?php

namespace App\Command;

use App\Service\TransferService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\RedisService;

#[AsCommand(name: 'app:process-transfers')]
class ProcessTransfersCommand extends Command
{
    public function __construct(
        private TransferService $transferService,
        private RedisService $redis
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Worker started. Listening for transfers...");

        while (true) {
            $payload = $this->redis->pop('transfers');
            if ($payload) {
                try {
                    $this->transferService->processTransfer($payload);
                    $output->writeln("Processed transaction: " . $payload['transactionId']);
                } catch (\Throwable $e) {
                    $output->writeln("<error>Failed transaction: " . $e->getMessage() . "</error>");
                }
            } else {
                sleep(1); // avoid busy loop
            }
        }

        return Command::SUCCESS;
    }
}
