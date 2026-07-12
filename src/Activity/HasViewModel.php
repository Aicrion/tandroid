<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Activity;

use Aicrion\Tandroid\Kernel\ViewModel\StateStore;
use Aicrion\Tandroid\Kernel\ViewModel\ViewModel;

/**
 * Opt-in trait giving a BotActivity access to a scoped ViewModel.
 * The ActivityManager injects the StateStore automatically after
 * instantiating any Activity that uses this trait, then persists
 * the ViewModel back to the store right after the lifecycle call
 * completes — the Activity code never touches cache keys directly.
 */
trait HasViewModel
{
    private StateStore $stateStore;

    private ?ViewModel $viewModel = null;

    /** @var class-string<ViewModel>|null */
    private ?string $viewModelClass = null;

    public function bindStateStore(StateStore $stateStore): void
    {
        $this->stateStore = $stateStore;
    }

    /**
     * @template T of ViewModel
     * @param class-string<T> $viewModelClass
     * @return T
     */
    protected function viewModel(string $viewModelClass): ViewModel
    {
        $this->viewModelClass = $viewModelClass;

        return $this->viewModel ??= $this->stateStore->resolve($viewModelClass, $this->update->chatId);
    }

    /**
     * Called automatically by the ActivityManager right after every
     * lifecycle hook completes — persists whichever ViewModel this
     * Activity resolved (if any) back to the StateStore. Activities
     * never need to call this themselves.
     */
    public function persistViewModel(): void
    {
        if ($this->viewModel !== null && $this->viewModelClass !== null) {
            $this->stateStore->persist($this->viewModelClass, $this->update->chatId, $this->viewModel);
        }
    }
}
