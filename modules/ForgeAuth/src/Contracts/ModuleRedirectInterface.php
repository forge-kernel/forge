<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Contracts;

/**
 * Interface for modules to specify custom redirect URLs after login.
 *
 * If a module implements this interface, it can return a custom redirect URL
 * that will be used when the user logs in and the intended URL matches
 * one of the module's routes.
 */
interface ModuleRedirectInterface
{
    /**
     * Get the redirect URL after login for this module.
     *
     * @param string|null $intendedUrl The URL the user was trying to access before login
     * @return string|null The redirect URL, or null to use default behavior
     */
    public function getRedirectAfterLogin(?string $intendedUrl = null): ?string;
}
