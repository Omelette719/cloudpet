<?php

namespace App\Services;

use Aws\Ec2\Ec2Client;
use Aws\S3\S3Client;

class MiniStackService
{
    public static function getClient(string $accessKey, string $secretKey): S3Client
    {
        return new S3Client([
            'version'                 => 'latest',
            'region'                  => config('services.ministack.region', 'id-1'),
            'endpoint'                => config('services.ministack.url', 'http://127.0.0.1:4566'),
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
    }

    public static function getEc2Client(): Ec2Client
    {
        return new Ec2Client([
            'version'  => 'latest',
            'region'   => config('services.ministack.region', 'id-1'),
            'endpoint' => config('services.ministack.url', 'http://127.0.0.1:4566'),
            'credentials' => [
                'key'    => config('services.ministack.aws_key', 'test'),
                'secret' => config('services.ministack.aws_secret', 'test'),
            ],
        ]);
    }

    public function createVolume(string $name, int $sizeGb): string
    {
        $region = config('services.ministack.region', 'id-1');

        $result = self::getEc2Client()->createVolume([
            'AvailabilityZone' => $region . 'a',
            'Size'             => $sizeGb,
            'VolumeType'       => 'gp2',
            'TagSpecifications' => [[
                'ResourceType' => 'volume',
                'Tags'         => [['Key' => 'Name', 'Value' => $name]],
            ]],
        ]);

        return $result['VolumeId'];
    }

    public function deleteVolume(string $volumeId): bool
    {
        self::getEc2Client()->deleteVolume(['VolumeId' => $volumeId]);
        return true;
    }

    public function attachVolume(string $volumeId, string $instanceRef): bool
    {
        self::getEc2Client()->attachVolume([
            'VolumeId'   => $volumeId,
            'InstanceId' => $this->toEc2InstanceId($instanceRef),
            'Device'     => '/dev/sdf',
        ]);
        return true;
    }

    public function detachVolume(string $volumeId, string $instanceRef): bool
    {
        self::getEc2Client()->detachVolume([
            'VolumeId'   => $volumeId,
            'InstanceId' => $this->toEc2InstanceId($instanceRef),
        ]);
        return true;
    }

    protected function toEc2InstanceId(string $ref): string
    {
        if (str_starts_with($ref, 'i-')) {
            return $ref;
        }
        return 'i-' . substr(md5($ref), 0, 17);
    }
}
