<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocalFileService
{
    public function uploadFile($file, $piNumber, $index)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = sprintf('%s-%02d.%s', $piNumber, $index, $extension);
            $filePath = $file->storeAs('invoices', $fileName, 'public');
            return Storage::url($filePath);
        } catch (\Exception $e) {
            Log::error('Error uploading file locally', ['exception' => $e]);
            return null;
        }
    }

    public function deleteFile($fileUrl)
    {
        try {
            $filePath = str_replace('/storage/', 'public/', $fileUrl);
            return Storage::delete($filePath);
        } catch (\Exception $e) {
            Log::error('Error deleting local file', ['exception' => $e]);
            return false;
        }
    }
}
