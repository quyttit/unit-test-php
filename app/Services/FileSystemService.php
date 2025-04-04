<?php

namespace App\Services;

use App\Interfaces\FileSystemInterface;

/**
 * Class FileSystemService
 * Implements file operations such as opening, writing, and closing files.
 */
class FileSystemService implements FileSystemInterface
{
    /**
     * Opens a file with the specified mode.
     *
     * @param string $filename The name of the file to open.
     * @param string $mode The mode in which to open the file (e.g., 'w', 'r').
     * @return resource|false The file handle on success, or false on failure.
     */
    public function openFile(string $filename, string $mode)
    {
        return fopen($filename, $mode);
    }

    /**
     * Writes data to a CSV file.
     *
     * @param resource $fileHandle The file handle to write to.
     * @param array $data The data to write as a CSV row.
     * @return void
     */
    public function writeCsv($fileHandle, array $data): void
    {
        fputcsv($fileHandle, $data);
    }

    /**
     * Closes an open file handle.
     *
     * @param resource $fileHandle The file handle to close.
     * @return void
     */
    public function closeFile($fileHandle): void
    {
        fclose($fileHandle);
    }
}
