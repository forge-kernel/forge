<?php

return [
    'registry' => [
        [
            'name' => 'github-public',
            'type' => 'git',
            'url' => 'https://github.com/user/public-modules',
            'branch' => 'main',
            'private' => false
        ],
        [
            'name' => 'github-private',
            'type' => 'git',
            'url' => 'https://github.com/user/private-modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('GITHUB_TOKEN')
            
        ],
        [
            'name' => 'gitlab',
            'type' => 'git',
            'url' => 'https://gitlab.com/user/modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('GITLAB_TOKEN')
        ],
        [
            'name' => 'bitbucket',
            'type' => 'git',
            'url' => 'https://bitbucket.org/user/modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('BITBUCKET_TOKEN')
        ],
        [
            'name' => 'azure-devops',
            'type' => 'git',
            'url' => 'https://dev.azure.com/organization/project/_git/modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('AZURE_DEVOPS_TOKEN')
        ]
    ],
    'cache_ttl' => 3600
];

