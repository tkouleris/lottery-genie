<?php

namespace App\Helpers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class File
{
    /**
     * @param $folder
     * @return bool|array
     * @throws FileNotFoundException
     */
    public static function load_xlsx_files($folder): bool|array
    {
        $folderPath = storage_path($folder);
        if (!is_dir($folderPath)) {
            throw new FileNotFoundException("Directory not found: {$folderPath}");
        }

        // Use PhpSpreadsheet to read .xlsx files
        return glob($folderPath . '/*.xlsx');
    }

    /**
     * @param $folder
     * @return string|null
     */
    public static function get_latest_file_date($folder): ?string
    {
        $folderPath = storage_path($folder);
        if (!is_dir($folderPath)) {
            return null;
        }

        $files = glob($folderPath . '/*.xlsx');
        if (empty($files)) {
            return null;
        }

        $latestTime = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
            }
        }

        return date('d/m/Y', $latestTime);
    }
}
