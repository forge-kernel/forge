<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Http\Response;

#[Routable]
#[UseMiddleware("web")]
#[Reactive]
final class SettingsController
{
    use ResponseHelper;
    use ViewHelper;

    #[State]
    public string $username = 'admin';

    #[State]
    public string $email = 'admin@example.com';

    #[State]
    public string $bio = 'A developer using Forge.';

    #[State]
    public bool $marketing = true;

    #[State]
    public string $message = '';

    #[Endpoint("/examples/settings")]
    #[Layout('main')]
    public function index(): Response
    {
        return $this->view("examples/settings", [
            'username' => $this->username,
            'email' => $this->email,
            'bio' => $this->bio,
            'marketing' => $this->marketing,
            'message' => $this->message
        ]);
    }

    #[Action]
    public function save(): void
    {
        if (strlen($this->username) < 3) {
            $this->message = 'Error: Username must be at least 3 characters.';
            return;
        }

        $this->message = 'Settings saved successfully at ' . date('H:i:s');
    }

    #[Action]
    public function reset(): void
    {
        $this->username = 'admin';
        $this->email = 'admin@example.com';
        $this->bio = 'A developer using Forge.';
        $this->marketing = true;
        $this->message = 'Settings reset.';
    }
}
