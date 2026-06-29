<?php

namespace Modules\ForgeRouter\Contracts;

use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Response;

interface DebugBarInterface
{
    public function addCollector(string $name, callable $collector): void;
    public function getData(): array;
    public function render(): string;
    public function injectDebugBarIfEnabled(Response $response, Container $container): Response;
    public function injectDebugBarIntoHtml(string $htmlContent, string $debugBarHtml, Container $container): string;
    public function shouldEnableDebugBar(Container $container): bool;
}
