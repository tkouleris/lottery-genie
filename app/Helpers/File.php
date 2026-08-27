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
}
