<?php

declare(strict_types=1);

namespace App\Controllers;

use Modules\ForgeWire\Attributes\Reactive;
use Modules\ForgeWire\Attributes\Action;
use Modules\ForgeWire\Attributes\State;
use Modules\ForgeWire\Traits\WithWireResponse;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;

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
