<?php

namespace Mykolavoitovych\CopyDatabase;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\CopyDatabaseDataJob;

class CopyDatabase extends Command
{
    protected $signature = 'database:copy';

    public function handle()
    {
        $copyFromConnection = config('copy-database.from');

        $tables = DB::connection($copyFromConnection)->getDoctrineSchemaManager()->listTableNames();

        //copy database structure
        $this->copyDatabaseStructure($tables);

        $this->info('Database structure copied successfully.');

        // Dispatch job to copy database data for each table
        foreach ($tables as $table) {
            CopyDatabaseDataJob::dispatch($table);
        }

        $this->info('Database copy jobs dispatched successfully.');
    }

    protected function copyDatabaseStructure($tables)
    {
        foreach ($tables as $table) {
            // Drop table if it exists in staging
            DB::statement("DROP TABLE IF EXISTS `$table`");

            // Copy the table structure from production to staging
            $copyQuery = "CREATE TABLE `$table` LIKE `$this->productionConnection`.`$table`";
            DB::statement($copyQuery);
        }
    }
}
