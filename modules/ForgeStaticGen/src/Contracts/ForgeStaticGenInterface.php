<?php

namespace Modules\ForgeStaticGen\Contracts;

interface ForgeStaticGenInterface
{
    public function build(string $contentDir): void;
}
