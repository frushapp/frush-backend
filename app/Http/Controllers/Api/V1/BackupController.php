<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;

class BackupController extends Controller
{

    public function downloadDatabaseDump()
    {
        $databaseName = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $host = env('DB_HOST');

        // Define the filename for the download
        $fileName = "backup-" . date('Y-m-d_H-i-s') . ".sql";

        // Prepare the command to dump the database
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($databaseName)
        );

        $response = new StreamedResponse(function () use ($command) {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // Set a timeout if the backup takes long

            // Run the command and stream the output directly to the response
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    // Handle errors if needed
                    echo 'Error: ' . $buffer;
                } else {
                    echo $buffer; // Send the output to the response
                }
            });
        });

        // Set headers for the response to indicate file download
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
