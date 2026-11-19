<?php

namespace App\Console\Commands;

use App\Services\Otp\OtpService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('otp:prune')]
#[Description('Delete email codes that expired or were used more than a day ago')]
class PruneOtpCodes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OtpService $otp): int
    {
        $this->info('Removed '.$otp->prune().' old code(s).');

        return self::SUCCESS;
    }
}
