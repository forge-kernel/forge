<?php

declare(strict_types=1);

namespace App\Controllers;

use Modules\ForgeWire\Attributes\Action;
use Modules\ForgeWire\Attributes\Reactive;
use Modules\ForgeWire\Attributes\State;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;

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
