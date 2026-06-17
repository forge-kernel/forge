<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Dto;

use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;

/**
 * Base notification DTO with common fields.
 * All channel-specific DTOs should extend this.
 */
abstract class NotificationDto extends BaseDto
{
  use DTOHelper;

  public function __construct(
    public string|array $to,
    public ?string $from = null,
    public ?string $subject = null,
    public ?string $body = null,
    public ?array $metadata = null,
  ) {
  }

  /**
   * Get the recipient(s) as an array.
   *
   * @return array
   */
  public function getToArray(): array
  {
    return is_array($this->to) ? $this->to : [$this->to];
  }
}
