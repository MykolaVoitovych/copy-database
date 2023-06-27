<?php

namespace Mykolavoitovych\CopyDatabase;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mykolavoitovych\CopyDatabase\Jobs\CopyDatabaseDataJob;

class CopyDatabase extends Command
{
    protected $signature = 'database:copy {only?}';

    public function handle()
    {
        $copyFromConnection = config('copy-database.from');

        $tables = DB::connection($copyFromConnection)->getDoctrineSchemaManager()->listTableNames();

        if ($only = $this->argument('only')) {
            if (!in_array($only, $tables)) {
                $this->error('Origin database doesn\'t have such table');
                return;
            }
            $structureTables = [$only];
            $dataTables = [$only];
        } else {
            $exceptTables = config('copy-database.except');
            $structureTables = array_diff($tables, $exceptTables['structure']);
            $dataTables = array_diff($tables, $exceptTables['data']);
        }

        //copy database structure
        $this->copyDatabaseStructure($structureTables, $copyFromConnection);

        $this->info('Database structure copied successfully.');

        $this->createDbJobsTable();

        // Dispatch job to copy database data for each table
        foreach ($dataTables as $table) {
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

    protected function createDbJobsTable()
    {
        if (!Schema::hasTable('db_import_jobs')) {
            Schema::create('db_import_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('table_name');
                $table->string('start_row');
                $table->string('end_row');
                $table->enum('status', ['processing', 'pending']);
                $table->timestamps();
            });
        } else {
            DB::table('db_import_jobs')->truncate();
        }
    }
}
