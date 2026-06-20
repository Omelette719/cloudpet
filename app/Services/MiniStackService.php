<?php

namespace App\Services;

use Aws\S3\S3Client;

class MiniStackService
{
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
}