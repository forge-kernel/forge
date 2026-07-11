<?php
declare(strict_types=1);

namespace Modules\ForgePackageManager\Contracts;

interface PackageManagerInterface
{
	public function installFromLock(): void;

	public function installModule(string $moduleName, ?string $version = null, ?string $forceCache = null, ?string $preservedPath = null, bool $autoTrustSource = false, ?string $configMode = null, bool $deferPostInstall = false): array;

	public function removeModule(string $moduleName): void;
}