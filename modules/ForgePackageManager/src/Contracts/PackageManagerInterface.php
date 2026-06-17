<?php
declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Contracts;

interface PackageManagerInterface
{
	public function installFromLock(): void;

	public function installModule(string $moduleName, ?string $version = null): void;

	public function removeModule(string $moduleName): void;
}