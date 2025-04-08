<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToDialog
{

    /**
     * The dialog title.
     */
    protected ?string $dialogTitle = null;

    /**
     * The dialog description.
     */
    protected ?string $dialogDescription = null;

    /**
     * The dialog component name.
     */
    protected string $dialogComponent = 'SCDialog';

    /**
     * The dialog width or size.
     */
    protected string $dialogSize = 'md';

    /**
     * Determines if the dialog is open.
     */
    protected bool $dialogOpen = false;

    /**
     * Set the dialog title.
     */
    public function dialogTitle(string | Closure | null $title): static
    {
        $this->dialogTitle = $title;

        return $this;
    }

    /**
     * Set the dialog description.
     */
    public function dialogDescription(string | Closure | null $description): static
    {
        $this->dialogDescription = $description;

        return $this;
    }

    /**
     * Set the dialog size/width.
     * Typical shadcn sizes: 'sm', 'md', 'lg', 'xl', '2xl', 'full'
     */
    public function dialogSize(string | Closure $size): static
    {
        $this->dialogSize = $size;

        return $this;
    }

    /**
     * Open the dialog.
     */
    public function openDialog(): static
    {
        $this->dialogOpen = true;

        return $this;
    }

    /**
     * Close the dialog.
     */
    public function closeDialog(): static
    {
        $this->dialogOpen = false;

        return $this;
    }

    /**
     * Get the dialog open state.
     */
    public function getDialogOpen(): bool
    {
        return $this->dialogOpen;
    }

    /**
     * Get the dialog title.
     */
    public function getDialogTitle(): ?string
    {
        return  $this->dialogTitle;
    }

    /**
     * Get the dialog description.
     */
    public function getDialogDescription(): ?string
    {
        return $this->dialogDescription;
    }

    /**
     * Get the dialog size.
     */
    public function getDialogSize(): string
    {
        return $this->dialogSize ?? 'md';
    }

    /**
     * Use this to override the default dialog component name.
     */
    public function dialogComponent(string | Closure $dialogComponent): static
    {
        $this->dialogComponent = $dialogComponent;

        return $this;
    }

    /**
     * Get the dialog component name.
     */
    public function getDialogComponent(): string
    {
        return  $this->dialogComponent ?? $this->getDefaultDialogComponent();
    }

    /**
     * Get the default dialog component name.
     */
    protected function getDefaultDialogComponent(): string
    {
        return 'SCDialog';
    }
}
