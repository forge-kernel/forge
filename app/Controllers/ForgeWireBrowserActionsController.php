<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

#[Middleware("web")]
#[Reactive]
final class ForgeWireBrowserActionsController
{
    use ControllerHelper;
    use ReactiveControllerHelper;

    #[Route("/forge-wire-browser-actions")]
    #[Action]
    public function index(Request $request): Response|string
    {
        return $this->view("pages/examples/browser-actions", []);
    }

    #[Action]
    public function testRedirect(): void
    {
        $this->redirect('/forge-wire-examples');
    }

    #[Action]
    public function testFlashSuccess(): void
    {
        $this->flash('success', 'This is a success message!');
    }

    #[Action]
    public function testFlashError(): void
    {
        $this->flash('error', 'This is an error message!');
    }

    #[Action]
    public function testFlashInfo(): void
    {
        $this->flash('info', 'This is an info message!');
    }

    #[Action]
    public function testFlashWarning(): void
    {
        $this->flash('warning', 'This is a warning message!');
    }

    #[Action]
    public function openModal(string $modalId, string $title = '', string $message = ''): void
    {
        modal($modalId, $title, $message);
    }

    #[Action]
    public function closeModal(?string $id = null): void
    {
        close_modal($id);
    }

    #[Action]
    public function showNotification(string $type = 'success', string $message = 'Notification triggered!'): void
    {
        notification($message, $type);
    }

    #[Action]
    public function triggerAnimation(string $selector = '.card', string $animation = 'fadeIn'): void
    {
        $this->dispatch('animateElement', [
            'selector' => $selector,
            'animation' => $animation,
        ]);
    }

    #[Action]
    public function combinedAction(): void
    {
        $this->flash('success', 'Action completed successfully!');
        $this->dispatch('closeModal');
        $this->redirect('/forge-wire-examples', 2);
    }
}
