<?php
declare(strict_types=1);

namespace App\Modules\ForgeLanding\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware('web')]
final class LandingController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Endpoint]
    #[Layout("ForgeComponents:public")]
    public function welcome(): Response
    {
        $user = $this->userContext->current();

        return $this->view(view: "welcome", data: [
            'currentUser' => $user,
        ]);
    }
}
