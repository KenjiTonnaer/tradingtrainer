<?php

namespace App\Console\Commands;

use App\Services\PiecesService;
use Illuminate\Console\Command;

class PiecesPing extends Command
{
    protected $signature = 'pieces:ping';

    protected $description = 'Check if Pieces OS is reachable';

    /**
     * Execute the console command.
     */
    public function handle(PiecesService $pieces): int
    {
        $this->components->info('Pinging Pieces OS at ' . $pieces->getBaseUrl() . ' ...');

        try {
            if ($pieces->ping()) {
                $this->components->success('Pieces OS is reachable.');
                return self::SUCCESS;
            }

            $this->components->error('Pieces OS returned an error response.');
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->components->error('Could not reach Pieces OS: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
