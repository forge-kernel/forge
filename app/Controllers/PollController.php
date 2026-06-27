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
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;

#[Routable]
#[UseMiddleware("web")]
#[Reactive]
final class PollController
{
    use ResponseHelper;
    use ViewHelper;

    #[State]
    public array $votes = [
        'PHP' => 5,
        'JS' => 3,
        'Python' => 2
    ];

    #[State]
    public bool $hasVoted = false;

    #[Endpoint("/examples/poll")]
    #[Layout('main')]
    public function index(): Response
    {
        return $this->view("examples/poll", [
            'votes' => $this->votes,
            'hasVoted' => $this->hasVoted,
            'total' => array_sum($this->votes)
        ]);
    }

    #[Action]
    public function vote(string $lang): void
    {
        if ($this->hasVoted)
            return;

        if (isset($this->votes[$lang])) {
            $this->votes[$lang]++;
            $this->hasVoted = true;
        }
    }
}
