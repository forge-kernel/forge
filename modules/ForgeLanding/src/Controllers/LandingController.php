<?php
declare(strict_types=1);

namespace App\Modules\ForgeLanding\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class LandingController
{
    use ControllerHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Route("/")]
    #[Layout("ForgeComponents:public")]
    public function welcome(): Response
    {
        $user = $this->userContext->current();

        return $this->view(view: "pages/welcome", data: [
            'currentUser' => $user,
        ]);
    }
}
