<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class DriveAuthorize extends Command
{
    protected $signature = 'drive:authorize';
    protected $description = 'One-time OAuth handshake to link this app to your Google Drive account';

    public function handle(GoogleDriveService $drive): int
    {
        $authUrl = $drive->getAuthUrl();

        $this->info('1. Open this URL in a browser signed into the Google account that owns your 5TB Drive:');
        $this->line($authUrl);
        $this->newLine();

        $code = $this->ask('2. Paste the authorization code you receive');

        try {
            $drive->exchangeCode($code);
        } catch (\Exception $e) {
            $this->error('Authorization failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Authorized successfully! Token saved to storage/app/google-drive-token.json');
        return self::SUCCESS;
    }
}
