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
    protected $batchSize;

    /**
     * Create a new job instance.
     *
     * @param string $table
     */
    public function __construct($table)
    {
        $this->copyFromConnection = config('copy-database.from');
        $this->table = $table;
        $this->batchSize = config('copy-database.batch-size');
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

        // Get the total number of rows in the table
        $totalRows = \DB::connection($this->copyFromConnection)->table($this->table)->count();

        if ($totalRows < $this->batchSize) {
            InsertTableRowsJob::dispatch($this->table, 0, $totalRows);
        } else {
            // Copy the data in batches
            for ($startRow = 0; $startRow < $totalRows; $startRow += $this->batchSize) {
                $endRow = min($startRow + $this->batchSize, $totalRows);

                \DB::table('db_import_jobs')
                    ->insert([
                        'table_name' => $this->table,
                        'start_row' => $startRow,
                        'end_row' => $endRow,
                        'status' => 'pending',
                    ]);
            }
        }

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
