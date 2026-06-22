<?php

namespace App\Services;

use Aws\Ec2\Ec2Client;
use Aws\Rds\RdsClient;
use Aws\S3\S3Client;

class MiniStackService
{
    // ── Client factories ─────────────────────────────────────────────────

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

    public static function getRdsClient(): RdsClient
    {
        return new RdsClient([
            'version'  => 'latest',
            'region'   => config('services.ministack.region', 'id-1'),
            'endpoint' => config('services.ministack.url', 'http://127.0.0.1:4566'),
            'credentials' => [
                'key'    => config('services.ministack.aws_key', 'test'),
                'secret' => config('services.ministack.aws_secret', 'test'),
            ],
        ]);
    }

    // ── EC2: Block Volume ────────────────────────────────────────────────

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

    // ── EC2: Compute Instance ────────────────────────────────────────────

    public function runInstance(string $name, string $imageId = 'ami-cloudpet'): string
    {
        $result = self::getEc2Client()->runInstances([
            'ImageId'      => $imageId,
            'InstanceType' => 't2.micro',
            'MinCount'     => 1,
            'MaxCount'     => 1,
            'TagSpecifications' => [[
                'ResourceType' => 'instance',
                'Tags'         => [['Key' => 'Name', 'Value' => $name]],
            ]],
        ]);

        return $result['Instances'][0]['InstanceId'] ?? '';
    }

    public function terminateInstance(string $instanceId): bool
    {
        self::getEc2Client()->terminateInstances([
            'InstanceIds' => [$instanceId],
        ]);
        return true;
    }

    public function stopInstance(string $instanceId): bool
    {
        self::getEc2Client()->stopInstances([
            'InstanceIds' => [$instanceId],
        ]);
        return true;
    }

    public function startInstance(string $instanceId): bool
    {
        self::getEc2Client()->startInstances([
            'InstanceIds' => [$instanceId],
        ]);
        return true;
    }

    // ── RDS: Managed Database ────────────────────────────────────────────

    public function createDBInstance(string $identifier, string $engine, string $dbName, string $masterUser, string $masterPassword): string
    {
        $engineMap = [
            'postgres-15' => ['Engine' => 'postgres', 'EngineVersion' => '15'],
            'postgres-14' => ['Engine' => 'postgres', 'EngineVersion' => '14'],
            'mysql-8'     => ['Engine' => 'mysql',    'EngineVersion' => '8.0'],
            'mysql-5.7'   => ['Engine' => 'mysql',    'EngineVersion' => '5.7'],
            'mariadb-10'  => ['Engine' => 'mariadb',  'EngineVersion' => '10.11'],
        ];

        $eng = $engineMap[$engine] ?? ['Engine' => 'mysql', 'EngineVersion' => '8.0'];

        $result = self::getRdsClient()->createDBInstance([
            'DBInstanceIdentifier' => $identifier,
            'DBInstanceClass'      => 'db.t3.micro',
            'Engine'               => $eng['Engine'],
            'EngineVersion'        => $eng['EngineVersion'],
            'MasterUsername'       => $masterUser,
            'MasterUserPassword'   => $masterPassword,
            'DBName'               => $dbName,
            'AllocatedStorage'     => 20,
        ]);

        return $result['DBInstance']['DBInstanceIdentifier'] ?? $identifier;
    }

    public function deleteDBInstance(string $identifier): bool
    {
        self::getRdsClient()->deleteDBInstance([
            'DBInstanceIdentifier' => $identifier,
            'SkipFinalSnapshot'    => true,
        ]);
        return true;
    }

    public function stopDBInstance(string $identifier): bool
    {
        self::getRdsClient()->stopDBInstance([
            'DBInstanceIdentifier' => $identifier,
        ]);
        return true;
    }

    public function startDBInstance(string $identifier): bool
    {
        self::getRdsClient()->startDBInstance([
            'DBInstanceIdentifier' => $identifier,
        ]);
        return true;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    protected function toEc2InstanceId(string $ref): string
    {
        if (str_starts_with($ref, 'i-')) {
            return $ref;
        }
        return 'i-' . substr(md5($ref), 0, 17);
    }
}
