<?php

namespace Mykolavoitovych\CopyDatabase;

use Illuminate\Console\Command;
use Mykolavoitovych\CopyDatabase\Jobs\InsertTableRowsJob;

class ImportDbJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:run-import-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //get all tables which don't have active import
        $tables = \DB::table('db_import_jobs')
            ->select('table_name')
            ->where('status', 'pending')
            ->groupBy('table_name')
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('db_import_jobs as tables')
                    ->whereRaw('tables.table_name = db_import_jobs.table_name')
                    ->where('status', 'processing');
            })
            ->get();

        foreach ($tables as $table) {
            $job = \DB::table('db_import_jobs')
                ->where('table_name', $table->table_name)
                ->where('status', 'pending')
                ->orderBy('id')
                ->first();

            if ($job) {
                \DB::table('db_import_jobs')->where('id', $job->id)->update([
                    'status' => 'processing'
                ]);
                InsertTableRowsJob::dispatch($job->table_name, $job->start_row, $job->end_row, $job->id);
            }
        }
    }
}
