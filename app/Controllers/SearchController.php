<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;

#[Reactive]
#[Middleware("web")]
final class SearchController
{
    use ControllerHelper;
    use ReactiveControllerHelper;

    #[State]
    public string $query = '';

    #[Route("/search")]
    public function index(Request $request): Response
    {
        define("TEST", true);

        $results = $this->search($this->query);
        $data = [
            "results" => $results,
            "query" => $this->query
        ];

        return $this->view("pages/search/index", $data);
    }

    #[Action]
    public function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        return [
            (object)['title' => "Result for: $query 1"],
            (object)['title' => "Result for: $query 2"],
            (object)['title' => "Result for: $query 3"],
        ];
    }
}
