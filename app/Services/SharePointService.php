<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class SharePointService
{
    protected Client $client;
    protected string $siteId;
    protected string $driveId;

    public function __construct()
    {
        $accessToken = Auth::user()->microsoft_token;
        if (!$accessToken) {
            throw new \Exception('Microsoft access token is missing or invalid.');
        }

        $this->client = new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Accept'        => 'application/json',
            ],
        ]);

        // Load these from .env
        $this->siteId  = config('services.microsoft.site_id');
        $this->driveId = config('services.microsoft.drive_id');

        if (!$this->siteId || !$this->driveId) {
            throw new \Exception('SharePoint siteId or driveId is not configured.');
        }
    }

    /**
     * Upload a file to SharePoint.
     */
    // public function uploadFile(UploadedFile $file, string $piNumber, int $index): ?array
    // {
    //     try {

    //         if ($file->getSize() > 10485760) { // 10 MB limit
    //             throw new \Exception('File size exceeds the maximum allowed limit of 10 MB.');
    //         }

    //         $extension  = $file->getClientOriginalExtension();
    //         $fileName   = sprintf('%s-%02d.%s', $piNumber, $index, $extension);
    //         $remotePath = "invoices/{$fileName}";

    //         $response = $this->client->put(
    //             "sites/{$this->siteId}/drives/{$this->driveId}/root:/{$remotePath}:/content",
    //             ['body' => file_get_contents($file->getRealPath())]
    //         );

    //         if ($response->getStatusCode() !== 201) {
    //             throw new \Exception('Failed to upload file to SharePoint.');
    //         }

    //         $data = json_decode($response->getBody(), true);

    //         return [
    //             'fileName'            => $fileName,
    //             'sharepoint_file_id'  => $data['id'] ?? null,
    //             'sharepoint_web_url'  => $data['webUrl'] ?? null,
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error('Error uploading file to SharePoint', ['exception' => $e]);
    //         return null;
    //     }
    // }

    public function uploadFile(UploadedFile $file, string $piNumber, int $index): ?array
    {
        try {
            if ($file->getSize() > 10485760) { // 10 MB limit
                throw new \Exception('File size exceeds the maximum allowed limit of 10 MB.');
            }
    
            $extension  = $file->getClientOriginalExtension();
            $fileName   = sprintf('%s-%02d.%s', $piNumber, $index, $extension);
    
            // Add year/month folder structure with numeric and textual month
            $currentDate = now(); // Laravel helper for the current date
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m. M'); // Format: "01. Jan", "02. Feb"
            $remotePath = "Invoices/{$year}/{$month}/{$fileName}";
    
            $response = $this->client->put(
                "sites/{$this->siteId}/drives/{$this->driveId}/root:/{$remotePath}:/content",
                ['body' => file_get_contents($file->getRealPath())]
            );
    
            // Log the response for debugging
            Log::info('SharePoint API Response', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
    
            // Accept both 200 and 201 as successful responses
            if (!in_array($response->getStatusCode(), [200, 201])) {
                throw new \Exception('Failed to upload file to SharePoint.');
            }
    
            $data = json_decode($response->getBody(), true);
    
            return [
                'fileName'            => $fileName,
                'sharepoint_file_id'  => $data['id'] ?? null,
                'sharepoint_web_url'  => $data['webUrl'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Error uploading file to SharePoint', [
                'exception' => $e->getMessage(),
                'response' => $e instanceof \GuzzleHttp\Exception\RequestException
                    ? $e->getResponse()->getBody()->getContents()
                    : null,
            ]);
            return null;
        }
    }

    /**
     * Delete a file using its SharePoint item ID.
     */
    public function deleteFileById(string $itemId): bool
    {
        try {
            $this->client->delete("sites/{$this->siteId}/drives/{$this->driveId}/items/{$itemId}");
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting file from SharePoint by ID', ['exception' => $e]);
            return false;
        }
    }

    /**
     * Delete a file by resolving the path (only if itemId not stored).
     */
    public function deleteFileByPath(string $fileName): bool
    {
        try {
            // Add year/month folder structure with numeric and textual month
            $currentDate = now(); // Laravel helper for the current date
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m. M'); // Format: "01. Jan", "02. Feb"
            $remotePath = "Invoices/{$year}/{$month}/{$fileName}";
    
            $response = $this->client->get("sites/{$this->siteId}/drives/{$this->driveId}/root:/{$remotePath}");
            $data = json_decode($response->getBody(), true);
            $itemId = $data['id'];
    
            return $this->deleteFileById($itemId);
        } catch (\Exception $e) {
            Log::error('Error resolving or deleting SharePoint file by path', ['exception' => $e]);
            return false;
        }
    }
}
