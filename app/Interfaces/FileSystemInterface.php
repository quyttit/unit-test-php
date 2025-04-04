<?php

namespace App\Interfaces;

/**
 * Interface FileSystemInterface
 * Defines methods for file operations such as opening, writing, and closing files.
 */
interface FileSystemInterface
{
    /**
     * Opens a file with the specified mode.
     *
     * @param string $filename The name of the file to open.
     * @param string $mode The mode in which to open the file (e.g., 'w', 'r').
     * @return resource|false The file handle on success, or false on failure.
     */
    public function openFile(string $filename, string $mode);

    /**
     * Writes data to a CSV file.
     *
     * @param resource $fileHandle The file handle to write to.
     * @param array $data The data to write as a CSV row.
     * @return void
     */
    public function writeCsv($fileHandle, array $data): void;

    /**
     * Closes an open file handle.
     *
     * @param resource $fileHandle The file handle to close.
     * @return void
     */
    public function closeFile($fileHandle): void;
}
