<?php

declare(strict_types=1);

use App\Modules\ForgeNotification\Services\ForgeNotificationService;
use Forge\Core\DI\Container;
use Forge\Exceptions\MissingServiceException;

if (!function_exists('notify')) {
  /**
   * Get the notification service instance.
   * Provides easy access to the notification system with fluent API.
   *
   * @return ForgeNotificationService
   * @throws MissingServiceException
   */
  function notify(): ForgeNotificationService
  {
    return Container::getInstance()->get(ForgeNotificationService::class);
  }
}
