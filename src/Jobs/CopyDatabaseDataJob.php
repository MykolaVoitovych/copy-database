<?php

namespace Mykolavoitovych\CopyDatabase\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CopyDatabaseDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $copyFromConnection;

    protected $table;

    /**
     * Create a new job instance.
     *
     * @param string $table
     */
    public function __construct($table)
    {
        $this->copyFromConnection = config('copy-database.from');
        $this->table = $table;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Disable foreign key checks temporarily
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Copy the data from production to staging
        $copyQuery = "INSERT INTO `$this->table` SELECT * FROM `$this->copyFromConnection`.`$this->table`";
        \DB::statement($copyQuery);

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
