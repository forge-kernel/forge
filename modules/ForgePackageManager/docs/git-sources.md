# Git Sources

Configure Git-based registries for GitHub, GitLab, Bitbucket, Azure DevOps, and self-hosted Git servers.

## GitHub

### Public Repository

```php
[
    'name' => 'github-public',
    'type' => 'git',
    'url' => 'https://github.com/user/repo',
    'branch' => 'main',
    'private' => false
]
```

### Private Repository

```php
[
    'name' => 'github-private',
    'type' => 'git',
    'url' => 'https://github.com/user/repo',
    'branch' => 'main',
    'private' => true
    'personal_token' => env('GITHUB_TOKEN')
]
```

### SSH URL

```php
[
    'name' => 'github-ssh',
    'type' => 'git',
    'url' => 'git@github.com:user/repo.git',
    'branch' => 'main',
    'private' => true
    'personal_token' => env('GITHUB_TOKEN')
]
```

## GitLab

### Public Repository

```php
[
    'name' => 'gitlab-public',
    'type' => 'git',
    'url' => 'https://gitlab.com/user/repo',
    'branch' => 'main'
    'private' => false
]
```

### Private Repository

```php
[
    'name' => 'gitlab-private',
    'type' => 'git',
    'url' => 'https://gitlab.com/user/repo',
    'branch' => 'main',
    'private' => true
    'personal_token' => env('GITLAB_TOKEN')
]
```

### Self-Hosted GitLab

```php
[
    'name' => 'gitlab-self-hosted',
    'type' => 'git',
    'url' => 'https://gitlab.company.com/user/repo',
    'branch' => 'main',
    'private' => true
    'personal_token' => env('GITLAB_TOKEN')
]
```

## Bitbucket

```php
[
    'name' => 'bitbucket',
    'type' => 'git',
    'url' => 'https://bitbucket.org/user/repo',
    'branch' => 'main',
    'private' => false
    'personal_token' => env('BITBUCKET_TOKEN')
]
```

## Azure DevOps

```php
[
    'name' => 'azure-devops',
    'type' => 'git',
    'url' => 'https://dev.azure.com/organization/project/_git/repo',
    'branch' => 'main',
    'private' => false
    'personal_token' => env('AZURE_DEVOPS_TOKEN')
]
```

## Self-Hosted Git

```php
[
    'name' => 'self-hosted-git',
    'type' => 'git',
    'url' => 'https://git.company.com/user/repo',
    'branch' => 'main',
    'private' => false
    'personal_token' => env('GIT_TOKEN')
]
```

## Token Generation

### GitHub

1. Go to Settings > Developer settings > Personal access tokens
2. Generate new token (classic)
3. Select `repo` scope for private repositories
4. Copy token to `.env` file

### GitLab

1. Go to User Settings > Access Tokens
2. Create token with `read_api` scope
3. Copy token to `.env` file

### Bitbucket

1. Go to Personal settings > App passwords
2. Create app password with repository read permissions
3. Copy password to `.env` file

## Branch and Tag Strategies

The `branch` field can reference:

- Branch names: `main`, `develop`, `master`
- Tag names: `v1.0.0`, `release-1.0`
- Commit hashes: `abc123def456`

## Registry Structure

Your Git repository should follow this structure:

```
repository/
├── modules.json
└── modules/
    └── module-name/
        └── version/
            └── module-name-version.zip
```

The `modules.json` file should contain:

```json
{
  "module-name": {
    "latest": "1.0.0",
    "versions": {
      "1.0.0": {
        "description": "Module description",
        "url": "module-name/1.0.0",
        "integrity": "sha256-hash"
      }
    }
  }
}
```

