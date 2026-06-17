<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Contracts;

use App\Modules\ForgeRouter\Http\Request;

/**
 * Interface for request preprocessors that can modify requests
 * before they are processed by the router. Multiple preprocessors
 * can be registered and will be called in order.
 */
interface RequestPreprocessorInterface
{
  /**
   * Preprocess a request before it's handled by the router.
   *
   * @param Request $request The request to preprocess
   * @return Request The preprocessed request
   */
  public function preprocess(Request $request): Request;
}
