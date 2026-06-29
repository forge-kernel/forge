<?php

declare(strict_types=1);

namespace Modules\ForgePackageManager\Tests;

use Modules\ForgePackageManager\Sources\FtpSource;
use Modules\ForgePackageManager\Sources\GitSource;
use Modules\ForgePackageManager\Sources\HttpSource;
use Modules\ForgePackageManager\Sources\LocalNetworkSource;
use Modules\ForgePackageManager\Sources\LocalSource;
use Modules\ForgePackageManager\Sources\SftpSource;
use Modules\ForgePackageManager\Sources\SourceFactory;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("package-manager")]
final class SourceFactoryTest extends TestCase
{
    #[Test("SourceFactory creates GitSource for git type")]
    public function factory_creates_git_source(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'https://github.com/user/repo',
            'branch' => 'main'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(GitSource::class, $source);
    }

    #[Test("SourceFactory creates SftpSource for sftp type")]
    public function factory_creates_sftp_source(): void
    {
        $config = [
            'type' => 'sftp',
            'host' => 'example.com',
            'port' => 22,
            'username' => 'user'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(SftpSource::class, $source);
    }

    #[Test("SourceFactory creates FtpSource for ftp type")]
    public function factory_creates_ftp_source(): void
    {
        $config = [
            'type' => 'ftp',
            'host' => 'ftp.example.com',
            'port' => 21,
            'username' => 'user',
            'password' => 'pass'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(FtpSource::class, $source);
    }

    #[Test("SourceFactory creates HttpSource for http type")]
    public function factory_creates_http_source(): void
    {
        $config = [
            'type' => 'http',
            'base_url' => 'https://example.com/modules'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(HttpSource::class, $source);
    }

    #[Test("SourceFactory creates LocalSource for local type")]
    public function factory_creates_local_source(): void
    {
        $config = [
            'type' => 'local',
            'path' => '/tmp/test'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(LocalSource::class, $source);
    }

    #[Test("SourceFactory creates LocalNetworkSource for network type")]
    public function factory_creates_network_source(): void
    {
        $config = [
            'type' => 'network',
            'path' => '/mnt/modules'
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(LocalNetworkSource::class, $source);
    }

    #[Test("SourceFactory defaults to GitSource when type not specified")]
    public function factory_defaults_to_git_source(): void
    {
        $config = [
            'url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'private' => false
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(GitSource::class, $source);
    }

    #[Test("SourceFactory merges environment variables for git source")]
    public function factory_merges_env_vars_for_git(): void
    {
        $_ENV['GITHUB_TOKEN'] = 'test-token';
        
        $config = [
            'type' => 'git',
            'url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'private' => false
        ];

        $source = SourceFactory::create($config);

        $this->assertInstanceOf(GitSource::class, $source);
        
        unset($_ENV['GITHUB_TOKEN']);
    }
}

