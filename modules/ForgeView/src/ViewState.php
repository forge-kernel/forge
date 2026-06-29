<?php

declare(strict_types=1);

namespace Modules\ForgeView;

final class ViewState
{
  /** @deprecated Use #[Layout] attribute instead. Kept for backward compat. */
  private ?array $layout = null;
  /** @deprecated Use $layoutSlots in view files instead. Kept for backward compat. */
  private array $sections = [];
  /** @deprecated Use $layoutSlots in view files instead. Kept for backward compat. */
  private string $currentSection = "";
  private bool $shouldSuppressLayout = false;
  private array $slots = [];

  /** @deprecated Use #[Layout] attribute instead. */
  public function getLayout(): ?array
  {
    return $this->layout;
  }

  /** @deprecated Use #[Layout] attribute instead. */
  public function setLayout(?array $layout): void
  {
    $this->layout = $layout;
  }

  /** @deprecated Use $layoutSlots in view files instead. */
  public function getSections(): array
  {
    return $this->sections;
  }

  /** @deprecated Use $layoutSlots in view files instead. */
  public function getSection(string $name): string
  {
    return $this->sections[$name] ?? "";
  }

  /** @deprecated Use $layoutSlots in view files instead. */
  public function startSection(string $name): void
  {
    $this->currentSection = $name;
    ob_start();
  }

  /** @deprecated Use $layoutSlots in view files instead. */
  public function endSection(): void
  {
    $this->sections[$this->currentSection] = ob_get_clean();
    $this->currentSection = "";
  }

  public function shouldSuppressLayout(): bool
  {
    return $this->shouldSuppressLayout;
  }

  public function setShouldSuppressLayout(bool $suppress): void
  {
    $this->shouldSuppressLayout = $suppress;
  }

  public function getSlots(): array
  {
    return $this->slots;
  }

  public function setSlots(array $slots): void
  {
    $this->slots = $slots;
  }

  public function getSlot(string $name = 'default', string $default = ''): string
  {
    if (!isset($this->slots[$name])) {
      return $default;
    }

    $slot = $this->slots[$name];

    return is_callable($slot)
      ? (string) $slot()
      : (string) $slot;
  }

  public function reset(): void
  {
    $this->layout = null;
    $this->shouldSuppressLayout = false;
    $this->sections = [];
    $this->currentSection = "";
    $this->slots = [];
  }
}
