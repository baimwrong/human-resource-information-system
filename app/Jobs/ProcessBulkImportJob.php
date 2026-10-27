<?php

namespace App\Jobs;

use App\Models\BulkImportLog;
use App\Services\BulkImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBulkImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes timeout for large CSVs
    public int $tries = 1;

    public function __construct(
        public string $type,
        public string $storedRelativePath,
        public string $originalFileName,
        public int $userId,
        public bool $rollbackOnError = false
    ) {
    }

    public function handle(BulkImportService $importService): void
    {
        $fullPath = Storage::path($this->storedRelativePath);

        if (! file_exists($fullPath)) {
            Log::error("ProcessBulkImportJob: Stored CSV file not found at {$fullPath}");
            return;
        }

        try {
            $importService->processImport(
                type: $this->type,
                filePath: $fullPath,
                fileName: $this->originalFileName,
                userId: $this->userId,
                rollbackOnError: $this->rollbackOnError
            );
        } catch (\Throwable $e) {
            Log::error("ProcessBulkImportJob failed: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $this->originalFileName,
            ]);
        } finally {
            // Clean up temporary stored CSV file after processing
            if (Storage::exists($this->storedRelativePath)) {
                Storage::delete($this->storedRelativePath);
            }
        }
    }
}
