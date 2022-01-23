<?php

namespace DanmerCC\Backuper\Console;

use DanmerCC\Backuper\Core\Backuper;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup full run and send to drive configuration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        Backuper::runFilesBackups("BK_" . env('APP_NAME') . date('Ymd_h_m_s'), true);
        return 0;
    }
}