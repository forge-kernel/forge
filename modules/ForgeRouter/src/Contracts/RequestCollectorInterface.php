<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Contracts;

use Modules\ForgeRouter\Http\Request;

/**
 * Interface for request collectors that can collect data from requests.
 * Modules implementing this interface will be automatically discovered
 * and called by the Kernel to collect request data (e.g., for debug bars).
 * Multiple collectors can be registered and will all be called.
 */
interface RequestCollectorInterface
{
  /**
   * Collect data from the request.
   *
   * @param Request $request The current request
   * @return mixed The collected data (can be array, object, etc.)
   */
  public function collect(Request $request): mixed;
}
