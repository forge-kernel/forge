<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\Session\SessionInterface;

#[Service]
#[RegisterMiddleware(group: 'web', order: 0, allowDuplicate: true, enabled: true)]
class SessionMiddleware extends Middleware
{
  public function __construct(
    private readonly SessionInterface $session
  ) {
  }
  public function handle(Request $request, callable $next): Response
  {
    $sessionEnabled = $_ENV['SESSION_ENABLED'] ?? true;
    if ($sessionEnabled === false || $sessionEnabled === 'false') {
      return $next($request);
    }

    $this->session->start();

    try {
      $response = $next($request);
    } finally {
      $this->session->save();
    }

    return $response;
  }
}
