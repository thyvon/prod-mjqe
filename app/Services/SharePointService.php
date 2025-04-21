<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use App\Services\MicrosoftTokenService;

class SharePointService
{
    protected Client $client;
    protected string $siteId;
    protected string $driveId;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient(): void
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

        $this->siteId  = config('services.microsoft.site_id');
        $this->driveId = config('services.microsoft.drive_id');

        if (!$this->siteId || !$this->driveId) {
            throw new \Exception('SharePoint siteId or driveId is not configured.');
        }
    }

    protected function refreshAccessToken(): void
    {
        MicrosoftTokenService::refreshToken();
        $this->initializeClient();
    }

    protected function handleTokenExpiration(\Exception $e): bool
    {
        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            $response = $e->getResponse();
            if ($response && $response->getStatusCode() === 401) {
                $body = json_decode($response->getBody(), true);
                if (isset($body['error']['code']) && $body['error']['code'] === 'InvalidAuthenticationToken') {
                    $this->refreshAccessToken();
                    return true;
                }
            }
        }
        return false;
    }

    public function uploadFile(UploadedFile $file, string $piNumber, string $purchasedBy, string $supplier): ?array
    {
        try {
            if ($file->getSize() > 536870912) { // 0.5 GB = 512 MB in bytes
                throw new \Exception('File size exceeds the maximum allowed limit of 0.5 GB.');
            }

            $extension  = $file->getClientOriginalExtension();

            // Generate a unique UID and convert it to uppercase
            $uid = strtoupper((string) \Illuminate\Support\Str::uuid());

            // Create a unique file name using the UID
            $fileName = sprintf('%s-%s.%s', $piNumber, $uid, $extension);

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

            // Add metadata to the uploaded file
            $fileId = $data['id'] ?? null;
            if ($fileId) {
                $metadata = [
                    'fields' => [
                        'Purchaser' => $purchasedBy, // Use "Purchaser" as the column name
                        'Supplier'  => $supplier,   // Use "Supplier" as the column name
                    ],
                ];

                $this->client->patch(
                    "sites/{$this->siteId}/drives/{$this->driveId}/items/{$fileId}/listItem",
                    ['json' => $metadata]
                );
            }

            return [
                'fileName'            => $fileName,
                'sharepoint_file_id'  => $data['id'] ?? null,
                'sharepoint_web_url'  => $data['webUrl'] ?? null,
            ];
        } catch (\Exception $e) {
            if ($this->handleTokenExpiration($e)) {
                // Retry the upload after refreshing the token
                return $this->uploadFile($file, $piNumber, $purchasedBy, $supplier);
            }

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
