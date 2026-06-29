<?php

declare(strict_types=1);

namespace App\Controllers;

use Modules\ForgeWire\Attributes\Action;
use Modules\ForgeWire\Attributes\Reactive;
use Modules\ForgeWire\Attributes\State;
use Modules\ForgeWire\Attributes\Validate;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;

#[Routable]
#[Reactive]
#[UseMiddleware("web")]
final class TodoListController
{
    use ResponseHelper;
    use ViewHelper;

    #[State(shared: true)]
    public int $counter = 0;

    #[Action]
    public function increment(): void
    {
        $this->counter++;
    }

    #[Action]
    public function decrement(): void
    {
        $this->counter--;
    }

    #[State]
    public array $todos = [
        ['id' => 1, 'text' => 'Learn Forge Framework', 'done' => true],
        ['id' => 2, 'text' => 'Master ForgeWire', 'done' => false],
    ];

    #[State]
    #[Validate('required|min:3|max:6')]
    public string $newTask = '';

    #[Endpoint("/examples/todo")]
    #[Layout('main')]
    public function index(Request $request): Response
    {
        return $this->view("examples/todo", [
            'todos' => $this->todos,
            'newTask' => $this->newTask,
            'counter' => $this->counter,
        ]);
    }

    #[Action(submit: true)]
    public function addTodo(): void
    {
        if (trim($this->newTask) === '')
            return;

        $this->todos[] = [
            'id' => time(),
            'text' => $this->newTask,
            'done' => false
        ];

        $this->newTask = '';
    }

    #[Action]
    public function toggleTodo(int $id): void
    {
        foreach ($this->todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['done'] = !$todo['done'];
                break;
            }
        }
    }

    #[Action]
    public function removeTodo(int $id): void
    {
        $this->todos = array_filter($this->todos, fn($t) => $t['id'] !== $id);
    }
}
