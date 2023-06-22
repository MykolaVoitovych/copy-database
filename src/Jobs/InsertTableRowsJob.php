<?php

namespace Mykolavoitovych\CopyDatabase\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class InsertTableRowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $copyFromConnection;

    protected $table;
    protected $startRow;
    protected $endRow;

    /**
     * Create a new job instance.
     *
     * @param string $productionConnection
     * @param string $stagingConnection
     * @param string $table
     * @param int $startRow
     * @param int $endRow
     */
    public function __construct($table, $startRow, $endRow)
    {
        $this->copyFromConnection = config('copy-database.from');
        $this->table = $table;
        $this->startRow = $startRow;
        $this->endRow = $endRow;
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
        $copyFromDBName = config("database.connections.$this->copyFromConnection.database");

        // Retrieve the rows from the production connection
        $rowsQuery = "SELECT * FROM `$copyFromDBName`.`$this->table`LIMIT $this->startRow, " . ($this->endRow - $this->startRow);
        $rows = DB::connection($this->copyFromConnection)->select($rowsQuery);

        // Insert the retrieved rows into the staging connection
        foreach ($rows as $row) {
            DB::table($this->table)->insert((array) $row);
        }
        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
