<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LocalFileService
{
    public function uploadFile($file)
    {
        try {
            $filePath = $file->store('uploads', 'public');
            return Storage::url($filePath);
        } catch (\Exception $e) {
            Log::error('Error uploading file locally', ['exception' => $e]);
            return null;
        }
    }

    public function deleteFile($fileUrl)
    {
        try {
            $filePath = str_replace('/storage/', '', $fileUrl);
            return Storage::disk('public')->delete($filePath);
        } catch (\Exception $e) {
            Log::error('Error deleting local file', ['exception' => $e]);
            return false;
        }
    }
}
