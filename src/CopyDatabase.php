<?php

namespace Mykolavoitovych\CopyDatabase;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mykolavoitovych\CopyDatabase\Jobs\CopyDatabaseDataJob;

class CopyDatabase extends Command
{
    protected $signature = 'database:copy';

    public function handle()
    {
        $copyFromConnection = config('copy-database.from');

        $tables = DB::connection($copyFromConnection)->getDoctrineSchemaManager()->listTableNames();

        //copy database structure
        $this->copyDatabaseStructure($tables, $copyFromConnection);

        $this->info('Database structure copied successfully.');

        // Dispatch job to copy database data for each table
        foreach ($tables as $table) {
            CopyDatabaseDataJob::dispatch($table);
        }

        $this->info('Database copy jobs dispatched successfully.');
    }

    protected function copyDatabaseStructure($tables, $copyFromConnection)
    {
        // Disable foreign key checks temporarily
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            // Drop table if it exists in staging
            DB::statement("DROP TABLE IF EXISTS `$table`");

            $copyFromDBName = config("database.connections.$copyFromConnection.database");

            // Get the table structure from production
            $structureQuery = "SHOW CREATE TABLE `$copyFromDBName`.`$table`";
            $structureResult = DB::connection($copyFromConnection)->selectOne($structureQuery);

            // Create the table with the same structure in staging
            $createTableQuery = $structureResult->{'Create Table'};
            DB::statement($createTableQuery);
        }

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
