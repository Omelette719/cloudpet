<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Http; 
use Exception;

class MiniStackService
{
    // <-- Perbaikan 2: Deklarasi properti
    protected string $baseUrl;
    protected string $apiKey;

    // <-- Perbaikan 3: Constructor untuk mengisi nilai baseUrl dan apiKey
    public function __construct()
    {
        // Mengakses 'url' sesuai config/services.php
        $this->baseUrl = config('services.ministack.url') 
                        ?? env('MINISTACK_URL', 'http://127.0.0.1:4566');

        // Mengakses 'key' sesuai config/services.php
        $this->apiKey = config('services.ministack.key') 
                        ?? env('MINISTACK_API_KEY', 'default-key-sementara');
    }

    /**
     * Menghasilkan instance S3Client yang sudah dikonfigurasi
     */
    public static function getClient(string $accessKey, string $secretKey): S3Client
    {
        return new S3Client([
            'version'                 => 'latest',
            'region'                  => 'id-1',
            'endpoint'                => env('MINISTACK_ENDPOINT', 'http://127.0.0.1:4566'),
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
    }

    /**
     * Memesan Block Volume baru di MiniStack
     */
    public function createVolume(string $name, int $sizeGb): string
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/volumes/provision", [
                'name' => $name,
                'size_gb' => $sizeGb
            ]);

        if (!$response->successful()) {
            throw new Exception("MiniStack API Error (Create Volume): " . $response->body());
        }

        return $response->json('data.volume_id'); // Return ID dari simulator
    }

    public function deleteVolume(string $providerId): bool
    {
        return Http::withToken($this->apiKey)
            ->delete("{$this->baseUrl}/volumes/{$providerId}")
            ->successful();
    }

    public function attachVolume(string $providerId, string $instanceProviderId): bool
    {
        return Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/volumes/{$providerId}/attach", [
                'compute_instance_id' => $instanceProviderId
            ])->successful();
    }

    public function detachVolume(string $providerId, string $instanceProviderId): bool
    {
        return Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/volumes/{$providerId}/detach", [
                'compute_instance_id' => $instanceProviderId
            ])->successful();
    }
}