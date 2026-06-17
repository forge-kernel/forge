<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Contracts;

use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

/**
 * Interface for response transformers that can modify responses
 * before they are sent. Multiple transformers can be registered
 * and will be called in order.
 */
interface ResponseTransformerInterface
{
  /**
   * Transform a response before it's sent.
   *
   * @param Response $response The response to transform
   * @param Request $request The current request
   * @return Response The transformed response
   */
  public function transform(Response $response, Request $request): Response;
}
