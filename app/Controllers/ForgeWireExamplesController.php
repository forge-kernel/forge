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
use App\Modules\ForgeRouter\Http\Response;

#[Middleware("web")]
#[Reactive]
final class ForgeWireExamplesController
{
  use ControllerHelper;

  #[State]
  public int $pollCount = 0;

  #[State]
  public int $counter = 0;

  #[State(shared: true)]
  public int $sharedCounter = 0;

  #[State]
  public int $step = 1;

  #[State]
  public string $immediateValue = '';

  #[State]
  public string $lazyValue = '';

  #[State]
  public string $deferValue = '';

  #[State]
  public string $debounceValue = '';

  #[State]
  public string $customDebounceValue = '';

  #[State]
  #[Validate('required|min:3', messages: ['required' => 'Name is required', 'min' => 'Name must be at least :value characters'])]
  public string $formName = '';

  #[State]
  #[Validate('required|email', messages: ['required' => 'Email is required', 'email' => 'Please enter a valid email address'])]
  public string $formEmail = '';

  #[State]
  public string $formMessage = '';

  #[State]
  public string $lastKey = '';

  #[State]
  public string $combinedValue = '';

  #[State]
  public int $loadingCounter = 0;

  #[Route("/forge-wire-examples")]
  public function index(): Response
  {
    return $this->view("pages/examples/forge-wire", [
      'pollCount' => $this->pollCount,
      'counter' => $this->counter,
      'sharedCounter' => $this->sharedCounter,
      'step' => $this->step,
      'immediateValue' => $this->immediateValue,
      'lazyValue' => $this->lazyValue,
      'deferValue' => $this->deferValue,
      'debounceValue' => $this->debounceValue,
      'customDebounceValue' => $this->customDebounceValue,
      'formName' => $this->formName,
      'formEmail' => $this->formEmail,
      'formMessage' => $this->formMessage,
      'lastKey' => $this->lastKey,
      'combinedValue' => $this->combinedValue,
      'loadingCounter' => $this->loadingCounter,
    ]);
  }

  #[Action]
  public function onPoll(): void
  {
    $this->pollCount++;
  }

  #[Action]
  public function increment(): void
  {
    $this->counter += $this->step;
  }

  #[Action]
  public function decrement(): void
  {
    $this->counter -= $this->step;
  }

  #[Action]
  public function reset(int $value = 0): void
  {
    $this->counter = $value;
  }

  #[Action]
  public function incrementBy(int $step): void
  {
    $this->counter += $step;
  }

  #[Action]
  public function incrementShared(): void
  {
    $this->sharedCounter++;
  }

  #[Action]
  public function decrementShared(): void
  {
    $this->sharedCounter--;
  }

  #[Action(submit: true)]
  public function saveForm(): void
  {
    $this->formMessage = 'Form saved successfully at ' . date('H:i:s');
  }

  #[Action]
  public function handleEnter(): void
  {
    $this->lastKey = 'Enter pressed at ' . date('H:i:s');
  }

  #[Action]
  public function handleEscape(): void
  {
    $this->lastKey = 'Escape pressed at ' . date('H:i:s');
  }

  #[Action]
  public function incrementLoading(): void
  {
    sleep(1);
    $this->loadingCounter++;
  }
}
