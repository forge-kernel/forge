<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;

#[Middleware("web")]
#[Reactive]
final class PollController
{
    use ControllerHelper;

    #[State]
    public array $votes = [
        'PHP' => 5,
        'JS' => 3,
        'Python' => 2
    ];

    #[State]
    public bool $hasVoted = false;

    #[Route("/examples/poll")]
    #[Layout('main')]
    public function index(): Response
    {
        return $this->view("pages/examples/poll", [
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
