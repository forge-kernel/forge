<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Traits\WithWireResponse;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;

#[Routable]
#[Reactive]
#[UseMiddleware("web")]
final class SearchController
{
    use ResponseHelper;
    use ViewHelper;
    use WithWireResponse;

    #[State]
    public string $query = '';

    #[Endpoint("/search")]
    #[Layout('main')]
    public function index(Request $request): Response
    {
        define("TEST", true);

        $results = $this->search($this->query);
        $data = [
            "results" => $results,
            "query" => $this->query
        ];

        return $this->view("search/index", $data);
    }

    #[Action]
    public function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        return [
            (object) ['title' => "Result for: $query 1"],
            (object) ['title' => "Result for: $query 2"],
            (object) ['title' => "Result for: $query 3"],
        ];
    }
}
