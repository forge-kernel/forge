<?php
declare(strict_types=1);

namespace Modules\ForgeLanding\Http;

use Modules\ForgeAuth\Contracts\UserContextInterface;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware('web')]
final class Landing
{
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
