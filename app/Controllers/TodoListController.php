<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Attributes\Validate;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;

#[Reactive]
#[Middleware("web")]
final class TodoListController
{
    use ControllerHelper;

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

    #[Route("/examples/todo")]
    #[Layout('main')]
    public function index(Request $request): Response
    {
        return $this->view("pages/examples/todo", [
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
