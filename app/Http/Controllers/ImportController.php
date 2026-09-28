<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCustomersRequest;
use App\Jobs\ImportCustomersJob;
use App\Models\ImportBatch;
use App\Services\CustomerImporter;
use App\Services\SpreadsheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Upload -> validate -> preview & map columns -> queue the import.
 *
 * The client's file only ever reaches our server. Nothing is forwarded to the
 * voice platform from the browser.
 */
class ImportController extends Controller
{
    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly CustomerImporter $importer,
    ) {
    }

    public function index(): View
    {
        return view('imports.index', [
            'batches' => ImportBatch::latest('id')->paginate(15),
        ]);
    }

    /** Step 1: receive the file and show a column-mapping preview. */
    public function store(UploadCustomersRequest $request)
    {
        $file = $request->file('file');

        $stored = $file->store('imports', 'local');
        $path   = Storage::disk('local')->path($stored);

        $batch = ImportBatch::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $path,
            'file_type'         => strtolower($file->getClientOriginalExtension()),
            'file_size'         => $file->getSize(),
            'status'            => ImportBatch::PENDING_MAPPING,
            'user_id'           => $request->user()->id,
        ]);

        try {
            $resolved = $this->reader->resolve($path, $batch->original_filename);
            $preview  = $this->reader->preview($resolved['path'], $resolved['type'], 10);
        } catch (\Throwable $e) {
            $batch->update(['status' => ImportBatch::FAILED, 'error_message' => $e->getMessage()]);
            Storage::disk('local')->delete($stored);

            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            $this->reader->cleanup();
        }

        if ($preview['headers'] === []) {
            $batch->update(['status' => ImportBatch::FAILED, 'error_message' => 'The file has no header row.']);
            Storage::disk('local')->delete($stored);

            return back()->withErrors(['file' => 'The file appears to be empty or has no header row.']);
        }

        $batch->update([
            'detected_headers' => $preview['headers'],
            'column_map'       => $this->importer->suggestMapping($preview['headers']),
            'total_rows'       => $preview['total'],
        ]);

        return redirect()->route('imports.map', $batch);
    }

    /** Step 2: the operator confirms or corrects the mapping. */
    public function map(ImportBatch $batch): View
    {
        abort_if($batch->status !== ImportBatch::PENDING_MAPPING, 404);

        $preview = ['rows' => []];

        try {
            $resolved = $this->reader->resolve($batch->stored_path, $batch->original_filename);
            $preview  = $this->reader->preview($resolved['path'], $resolved['type'], 8);
        } catch (\Throwable) {
            // The mapping screen still works without sample rows.
        } finally {
            $this->reader->cleanup();
        }

        return view('imports.map', [
            'batch'   => $batch,
            'preview' => $preview,
            'fields'  => CustomerImporter::FIELDS,
        ]);
    }

    /** Step 3: persist the mapping and queue the parse. */
    public function process(Request $request, ImportBatch $batch)
    {
        abort_if($batch->status !== ImportBatch::PENDING_MAPPING, 404);

        $headers = (array) $batch->detected_headers;

        $data = $request->validate([
            'map'   => ['required', 'array'],
            'map.*' => ['nullable', 'string'],
        ]);

        $map = [];

        foreach ($data['map'] as $field => $header) {
            if (! in_array($field, CustomerImporter::FIELDS, true)) {
                continue;
            }

            if ($header !== null && $header !== '' && in_array($header, $headers, true)) {
                $map[$field] = $header;
            }
        }

        if (! isset($map['phone_number'])) {
            return back()->withErrors(['map' => 'Map a column to Phone Number -- calls cannot be placed without it.']);
        }

        $batch->update(['column_map' => $map, 'status' => ImportBatch::QUEUED]);

        ImportCustomersJob::dispatch($batch->id);

        return redirect()->route('imports.show', $batch)
            ->with('status', 'Import queued. Results will appear here as rows are processed.');
    }

    public function show(ImportBatch $batch): View
    {
        return view('imports.show', [
            'batch'     => $batch,
            'customers' => $batch->customers()->limit(25)->get(),
        ]);
    }

    /** Polled by the import screen while the job runs. */
    public function status(ImportBatch $batch): JsonResponse
    {
        return response()->json([
            'status'     => $batch->status,
            'label'      => $batch->status_label,
            'finished'   => $batch->isFinished(),
            'total'      => $batch->total_rows,
            'valid'      => $batch->valid_rows,
            'rejected'   => $batch->rejected_rows,
            'duplicates' => $batch->duplicate_rows,
            'error'      => $batch->error_message,
        ]);
    }

    public function destroy(ImportBatch $batch)
    {
        abort_if($batch->status === ImportBatch::PROCESSING, 409, 'This import is still running.');

        if ($batch->stored_path && file_exists($batch->stored_path)) {
            @unlink($batch->stored_path);
        }

        $batch->delete();

        return redirect()->route('imports.index')->with('status', 'Import record removed. Imported customers were kept.');
    }
}
