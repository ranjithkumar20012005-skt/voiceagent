<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Reads customer sheets uploaded by the client.
 *
 * Supports .csv / .xlsx / .xls directly, and .zip when it contains exactly one
 * of those. ZIP handling is deliberately paranoid: archives are the one place
 * a file upload can turn into path traversal, a zip bomb or a dropped
 * executable.
 */
class SpreadsheetReader
{
    public const SUPPORTED = ['csv', 'xlsx', 'xls'];

    /** Extensions we refuse to extract even if a zip claims otherwise. */
    private const BLOCKED_IN_ZIP = [
        'exe', 'dll', 'bat', 'cmd', 'com', 'scr', 'msi', 'ps1', 'sh', 'bash',
        'php', 'phtml', 'jar', 'js', 'vbs', 'py', 'pl', 'rb', 'so', 'dylib',
        'zip', 'rar', '7z', 'gz', 'tar', 'bz2', 'xz',
    ];

    /** Temporary directories created during this request, removed on destruct. */
    private array $tempDirs = [];

    public function __destruct()
    {
        $this->cleanup();
    }

    /** Delete anything we extracted. Safe to call more than once. */
    public function cleanup(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->deleteDirectory($dir);
        }

        $this->tempDirs = [];
    }

    /**
     * Resolve an uploaded path to a readable spreadsheet, unwrapping a zip if
     * necessary.
     *
     * @return array{path:string, type:string}
     */
    public function resolve(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($ext, self::SUPPORTED, true)) {
            return ['path' => $path, 'type' => $ext];
        }

        if ($ext === 'zip') {
            return $this->extractSingleSheetFromZip($path);
        }

        throw new RuntimeException('Unsupported file type. Upload a .csv, .xlsx, .xls or .zip file.');
    }

    /**
     * Read the header row and up to $limit data rows -- used for the preview
     * and column-mapping step.
     *
     * @return array{headers:list<string>, rows:list<array<int,string>>, total:int}
     */
    public function preview(string $path, string $type, int $limit = 10): array
    {
        [$headers, $rows, $total] = $this->read($path, $type, $limit);

        return ['headers' => $headers, 'rows' => $rows, 'total' => $total];
    }

    /**
     * Stream every data row as an associative array keyed by header name.
     *
     * @return \Generator<int, array<string,string>>
     */
    public function rows(string $path, string $type): \Generator
    {
        if ($type === 'csv') {
            yield from $this->csvRows($path);

            return;
        }

        yield from $this->excelRows($path);
    }

    // =================================================================
    // Reading
    // =================================================================

    /** @return array{0:list<string>,1:list<array<int,string>>,2:int} */
    private function read(string $path, string $type, int $limit): array
    {
        $headers = [];
        $rows    = [];
        $total   = 0;

        foreach ($this->rows($path, $type) as $row) {
            if ($headers === []) {
                $headers = array_keys($row);
            }

            $total++;

            if (count($rows) < $limit) {
                $rows[] = array_values($row);
            }
        }

        return [$headers, $rows, $total];
    }

    /** @return \Generator<int, array<string,string>> */
    private function csvRows(string $path): \Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Could not open the uploaded file.');
        }

        try {
            // Strip a UTF-8 BOM so the first header is not "\xEF\xBB\xBFname".
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headers = null;

            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if ($data === [null] || $data === false) {
                    continue; // blank line
                }

                if ($headers === null) {
                    $headers = $this->normaliseHeaders($data);
                    continue;
                }

                if ($this->isBlankRow($data)) {
                    continue;
                }

                yield $this->combine($headers, $data);
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return \Generator<int, array<string,string>> */
    private function excelRows(string $path): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false); // we need number formats to detect dates
        $reader->setReadEmptyCells(false);

        $sheet = $reader->load($path)->getActiveSheet();

        $highestRow    = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = null;

        for ($r = 1; $r <= $highestRow; $r++) {
            $data = [];

            for ($c = 1; $c <= $highestColumn; $c++) {
                $cell  = $sheet->getCell([$c, $r]);
                $value = $cell->getValue();

                // Excel stores dates as serial numbers; render them ISO.
                if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
                    $value = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
                }

                $data[] = $value === null ? '' : trim((string) $value);
            }

            if ($headers === null) {
                $headers = $this->normaliseHeaders($data);
                continue;
            }

            if ($this->isBlankRow($data)) {
                continue;
            }

            yield $this->combine($headers, $data);
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>   $data
     * @return array<string,string>
     */
    private function combine(array $headers, array $data): array
    {
        $out = [];

        foreach ($headers as $i => $header) {
            $out[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
        }

        return $out;
    }

    /**
     * Header names become the mapping keys, so they must be unique and
     * non-empty even when the client's sheet is messy.
     *
     * @return list<string>
     */
    private function normaliseHeaders(array $raw): array
    {
        $headers = [];
        $seen    = [];

        foreach ($raw as $i => $value) {
            $name = trim((string) $value);

            if ($name === '') {
                $name = 'column_' . ($i + 1);
            }

            $key = strtolower($name);

            if (isset($seen[$key])) {
                $name .= '_' . (++$seen[$key]);
            } else {
                $seen[$key] = 1;
            }

            $headers[] = $name;
        }

        return $headers;
    }

    private function isBlankRow(array $data): bool
    {
        foreach ($data as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    // =================================================================
    // ZIP handling
    // =================================================================

    /** @return array{path:string, type:string} */
    private function extractSingleSheetFromZip(string $zipPath): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('ZIP uploads are not available on this server (missing zip extension).');
        }

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('The ZIP file could not be opened.');
        }

        try {
            $maxTotal = (int) config('sarvam.upload.zip_max_uncompressed');
            $maxRatio = (int) config('sarvam.upload.zip_max_ratio');

            $compressedTotal   = max(1, filesize($zipPath) ?: 1);
            $uncompressedTotal = 0;
            $candidate         = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if ($stat === false) {
                    continue;
                }

                $name = $stat['name'];

                // Directories are fine to skip; macOS resource forks too.
                if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || str_starts_with(basename($name), '._')) {
                    continue;
                }

                // Path traversal / absolute paths / drive letters.
                if (str_contains($name, '..') || str_starts_with($name, '/') || str_starts_with($name, '\\') || preg_match('/^[A-Za-z]:/', $name)) {
                    throw new RuntimeException('The ZIP file contains an unsafe path and was rejected.');
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($ext, self::BLOCKED_IN_ZIP, true)) {
                    throw new RuntimeException("The ZIP file contains a disallowed entry (.{$ext}) and was rejected.");
                }

                $uncompressedTotal += (int) $stat['size'];

                if ($uncompressedTotal > $maxTotal) {
                    throw new RuntimeException('The ZIP file expands beyond the allowed size limit.');
                }

                if (! in_array($ext, self::SUPPORTED, true)) {
                    throw new RuntimeException("The ZIP file contains an unsupported entry (.{$ext}).");
                }

                if ($candidate !== null) {
                    throw new RuntimeException('The ZIP file must contain exactly one spreadsheet or CSV file.');
                }

                $candidate = ['index' => $i, 'name' => $name, 'ext' => $ext];
            }

            if ($candidate === null) {
                throw new RuntimeException('The ZIP file does not contain a .csv, .xlsx or .xls file.');
            }

            if (intdiv($uncompressedTotal, $compressedTotal) > $maxRatio) {
                throw new RuntimeException('The ZIP file has a suspicious compression ratio and was rejected.');
            }

            // Extract by stream under a generated name -- we never trust the
            // archive's own filename on disk.
            $dir = $this->makeTempDir();
            $out = $dir . DIRECTORY_SEPARATOR . 'import.' . $candidate['ext'];

            $stream = $zip->getStream($candidate['name']);

            if ($stream === false) {
                throw new RuntimeException('The ZIP entry could not be read.');
            }

            $dest    = fopen($out, 'wb');
            $written = 0;

            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 8192);

                    if ($chunk === false) {
                        break;
                    }

                    $written += strlen($chunk);

                    if ($written > $maxTotal) {
                        throw new RuntimeException('The ZIP entry expands beyond the allowed size limit.');
                    }

                    fwrite($dest, $chunk);
                }
            } finally {
                fclose($stream);
                fclose($dest);
            }

            return ['path' => $out, 'type' => $candidate['ext']];
        } finally {
            $zip->close();
        }
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import_' . bin2hex(random_bytes(8));

        if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('Could not create a temporary directory for the upload.');
        }

        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($full) ? $this->deleteDirectory($full) : @unlink($full);
        }

        @rmdir($dir);
    }
}
