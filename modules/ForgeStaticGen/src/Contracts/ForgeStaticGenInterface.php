<?php

namespace App\Modules\ForgeStaticGen\Contracts;

interface ForgeStaticGenInterface
{
    public function build(string $contentDir): void;
}
