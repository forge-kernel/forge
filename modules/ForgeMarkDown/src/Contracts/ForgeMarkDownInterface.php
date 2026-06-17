<?php

declare(strict_types=1);

namespace App\Modules\ForgeMarkDown\Contracts;

interface ForgeMarkDownInterface
{
    public function parse(string $markdown): string;
    public function parseFile(string $path): array;
}
