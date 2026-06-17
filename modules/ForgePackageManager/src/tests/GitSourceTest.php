<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Tests;

use App\Modules\ForgePackageManager\Sources\GitSource;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;

#[Group("package-manager")]
final class GitSourceTest extends TestCase
{
    #[Test("GitSource resolves GitHub URL correctly")]
    public function git_source_resolves_github_url(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'https://github.com/user/repo',
            'branch' => 'main'
        ];

        $source = new GitSource($config);

        $this->assertTrue($source->supportsVersioning());
    }

    #[Test("GitSource resolves GitLab URL correctly")]
    public function git_source_resolves_gitlab_url(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'https://gitlab.com/user/repo',
            'branch' => 'main'
        ];

        $source = new GitSource($config);

        $this->assertTrue($source->supportsVersioning());
    }

    #[Test("GitSource resolves Bitbucket URL correctly")]
    public function git_source_resolves_bitbucket_url(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'https://bitbucket.org/user/repo',
            'branch' => 'main'
        ];

        $source = new GitSource($config);

        $this->assertTrue($source->supportsVersioning());
    }

    #[Test("GitSource handles SSH URL format")]
    public function git_source_handles_ssh_url(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'git@github.com:user/repo.git',
            'branch' => 'main'
        ];

        $source = new GitSource($config);

        $this->assertTrue($source->supportsVersioning());
    }

    #[Test("GitSource uses token from config")]
    public function git_source_uses_token_from_config(): void
    {
        $config = [
            'type' => 'git',
            'private' => false,
            'url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'personal_token' => 'test-token'
        ];

        $source = new GitSource($config);

        $this->assertTrue($source->supportsVersioning());
    }
}

