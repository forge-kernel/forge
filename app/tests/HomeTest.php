<?php

declare(strict_types=1);

namespace App\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\Response;

#[Group("http")]
final class HomeTest extends TestCase
{
  //#[Skip("Waiting on TestHttpService implementation")]
  #[Test("Home / route is working")]
  public function home_route_is_ok(): void
  {
    $response = $this->get("/");
    $this->assertHttpStatus(200, $response);
  }

  #[Test("POST / with invalid data redirects back")]
  public function register_route_returns_redirect_on_validation_error(): void
  {
    $response = $this->post(
      "/",
      $this->withCsrf([
        "email" => "invalid-email",
        "password" => "123",
      ]),
    );

    $this->assertHttpStatus(302, $response);
  }

  #[Test("POST /auth/login with invalid data redirects back")]
  public function login_route_returns_redirect_on_validation_error(): void
  {
    $response = $this->post(
      "/auth/login",
      $this->withCsrf([
        "identifier" => "jeremias",
        "password" => "123456",
      ]),
    );

    $this->assertHttpStatus(302, $response);
  }

  #[Test("PATCH /1 without auth returns 403")]
  public function update_user_route_returns_unauthorized_if_not_authenticated(): void
  {
    $response = $this->patch(
      "/1",
      [
        "identifier" => "newuser",
        "email" => "new@example.com",
      ],
      $this->csrfHeaders(),
    );

    $this->assertHttpStatus(401, $response);
  }
}
