<?php

namespace App\Http\Controllers\Api\V1;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Carbon\Carbon;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;

class BackupController extends Controller
{

    public function downloadDatabaseDump()
    {
        // Set the database information
        $databaseName = env('tast_safemax');
        $username = env('tast_safemax');
        $password = env('tast_safemax');
        $host = env('127.0.0.1');
        
        // Define the filename with the current timestamp
        $fileName = "backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".sql";
        $filePath = storage_path("app/public/{$fileName}");
        
        // Command to export the database
        $command = "mysqldump --user={$username} --password={$password} --host={$host} {$databaseName} > {$filePath}";
        
        $process = new Process([$command]);
        $process->setTimeout(300); // Set a timeout if the backup takes long

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Database backup failed.'], 500);
        }

        // Return the file as a download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
