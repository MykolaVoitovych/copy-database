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
    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @param string $productionConnection
     * @param string $stagingConnection
     * @param string $table
     * @param int $startRow
     * @param int $endRow
     */
    public function __construct($table, $startRow, $endRow, $jobId = null)
    {
        $this->copyFromConnection = config('copy-database.from');
        $this->table = $table;
        $this->startRow = $startRow;
        $this->endRow = $endRow;
        $this->jobId = $jobId;
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

        //getIndexes
        $indexes = \Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($this->table);

        $recordsQuery = DB::connection($this->copyFromConnection)
            ->table($this->table);

        if (!empty($indexes['primary'])) {
            $columns = $indexes['primary']->getColumns();
            $recordsQuery->orderBy($columns[0]);
        }

        $records = $recordsQuery->skip($this->startRow)
            ->take($this->endRow - $this->startRow)
            ->get();

        // Insert the retrieved rows into the staging connection
        $data = [];
        foreach ($records as $record) {
            $data[] = get_object_vars($record);
        }

        DB::table($this->table)->insert($data);

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if ($this->jobId) {
            \DB::table('db_import_jobs')->where('id', $this->jobId)->delete();
        }
    }
}
