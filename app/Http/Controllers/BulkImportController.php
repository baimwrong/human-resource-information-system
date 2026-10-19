<?php

namespace App\Http\Controllers;

use App\Models\BulkImportLog;
use App\Services\BulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkImportController extends Controller
{
    protected BulkImportService $importService;

    public function __construct(BulkImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        $recentLogs = BulkImportLog::with('user')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $stats = [
            'total_imports' => BulkImportLog::count(),
            'successful_imports' => BulkImportLog::where('status', 'completed')->count(),
            'partial_imports' => BulkImportLog::where('status', 'partial')->count(),
            'failed_imports' => BulkImportLog::where('status', 'failed')->count(),
        ];

        return view('bulk-import.index', compact('recentLogs', 'stats'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'import_type' => ['required', 'in:employees,departments,attendance,salaries'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'rollback_on_error' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('csv_file');
        $type = $request->input('import_type');
        $fileName = $file->getClientOriginalName();
        $rollback = $request->boolean('rollback_on_error');

        $tempPath = $file->getRealPath();

        try {
            $result = $this->importService->processImport(
                type: $type,
                filePath: $tempPath,
                fileName: $fileName,
                userId: Auth::id(),
                rollbackOnError: $rollback
            );

            if ($result['status'] === 'completed') {
                return redirect()->route('bulk-import.logs')->with(
                    'success',
                    "Bulk import completed successfully! {$result['successful']} records imported."
                );
            } elseif ($result['status'] === 'partial') {
                return redirect()->route('bulk-import.logs')->with(
                    'warning',
                    "Bulk import partially completed: {$result['successful']} succeeded, {$result['failed']} failed. Check the error log for details."
                );
            } else {
                return redirect()->route('bulk-import.logs')->with(
                    'error',
                    "Bulk import failed! {$result['failed']} errors encountered. Check the error log for details."
                );
            }
        } catch (\Throwable $e) {
            return back()->with('error', "Import error: " . $e->getMessage());
        }
    }

    public function downloadTemplate(string $type)
    {
        try {
            $content = $this->importService->getTemplateCsv($type);
            $fileName = "template_{$type}.csv";

            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\InvalidArgumentException $e) {
            abort(404, 'Template type not found.');
        }
    }

    public function logs()
    {
        $logs = BulkImportLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('bulk-import.logs', compact('logs'));
    }

    public function downloadErrorReport(BulkImportLog $log)
    {
        $errors = $log->error_details ?? [];

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import_errors_' . $log->id . '.csv"',
        ];

        $sanitizeCsv = function ($val) {
            $val = (string)($val ?? '');
            if (strlen($val) > 0 && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"])) {
                return "'" . $val;
            }
            return $val;
        };

        $callback = function () use ($errors, $sanitizeCsv) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Row Number', 'Identifier', 'Error Reason']);

            foreach ($errors as $err) {
                fputcsv($handle, [
                    $sanitizeCsv($err['row'] ?? 'N/A'),
                    $sanitizeCsv($err['identifier'] ?? 'N/A'),
                    $sanitizeCsv($err['error'] ?? 'Unknown error'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
