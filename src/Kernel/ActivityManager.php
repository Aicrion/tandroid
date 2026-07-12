<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Activity\BackStackEntry;
use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Intent\IntentFlag;
use Aicrion\Tandroid\Kernel\ViewModel\StateStore;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Update\UpdateType;
use Aicrion\Tandroid\Api\Telegram;
use Psr\Container\ContainerInterface;

/**
 * The heart of the framework — equivalent to Android's
 * ActivityManagerService. Receives a resolved Intent, instantiates
 * (via the DI container) the target Activity, drives its lifecycle,
 * and mutates the per-user back-stack in Redis/cache accordingly.
 *
 * Every NavigationRequest returned from a lifecycle hook is followed
 * transparently — a chain of startActivity() calls resolves in a
 * single incoming Update without any extra round-trip to Telegram.
 */
final class ActivityManager
{
    private const int MAX_CHAIN_DEPTH = 8;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly IntentResolver $intentResolver,
        private readonly BackStackStore $backStack,
        private readonly ?StateStore $stateStore = null,
    ) {}

    /** @return array{view:?View,intentFlags:list<IntentFlag>} */
    public function dispatch(Update $update): array
    {
        $intent = $this->intentResolver->resolve($update);
        $view = $this->handle($update, $intent, depth: 0);

        // Mandatory: stops the client-side loading spinner on every
        // inline button tap, regardless of whether the Activity
        // itself customized the toast via CallbackQueryAnswer.
        if ($update->type === UpdateType::CallbackQuery) {
            $callbackQueryId = $update->raw['callback_query']['id'] ?? null;

            if ($callbackQueryId !== null) {
                Telegram::callback($callbackQueryId)->send();
            }
        }

        return ['view' => $view, 'intentFlags' => $intent->getFlags()];
    }

    private function handle(Update $update, Intent $intent, int $depth): ?View
    {
        if ($depth >= self::MAX_CHAIN_DEPTH) {
            throw new \RuntimeException('Intent navigation chain exceeded max depth — possible loop.');
        }

        $current = $this->backStack->current($update->chatId);
        $isReentry = $current !== null
            && $current->activityClass === $intent->activityClass
            && $intent->hasFlag(IntentFlag::SingleTop) === false
            && $update->callbackData !== null;

        $activity = $this->instantiate($intent->activityClass);
        $activity->bindUpdate($update);
        $this->injectStateStore($activity);

        if ($isReentry) {
            $navigation = $activity->onNewIntent($intent);
        } else {
            $this->pauseCurrent($update->chatId);
            $navigation = $activity->onCreate($intent);
        }

        $navigation ??= $activity->onResume();

        $this->persistViewModel($activity);

        // A lifecycle hook asked to navigate further (startActivity())
        // or to pop the stack (finishWithResult()) — follow the chain
        // right now, inside the same incoming Update, instead of
        // waiting for another round-trip from Telegram.
        if ($navigation !== null) {
            return $this->followNavigation($update, $navigation, $intent, $depth);
        }

        $this->persistStack($update->chatId, $intent);

        return $activity->getContentView();
    }

    private function followNavigation(Update $update, NavigationRequest $navigation, Intent $finishingIntent, int $depth): ?View
    {
        if ($navigation->isFinish) {
            return $this->goBack($update, $finishingIntent);
        }

        /** @var Intent $nextIntent */
        $nextIntent = $navigation->intent;

        return $this->handle($update, $nextIntent, $depth + 1);
    }

    private function pauseCurrent(int $chatId): void
    {
        $current = $this->backStack->current($chatId);

        if ($current === null) {
            return;
        }

        $activity = $this->instantiate($current->activityClass);
        $activity->onPause();
    }

    private function persistStack(int $chatId, Intent $intent): void
    {
        $clear = $intent->hasFlag(IntentFlag::ClearBackStack) || $intent->hasFlag(IntentFlag::NewTask);

        if ($intent->hasFlag(IntentFlag::NoHistory)) {
            return;
        }

        $entry = new BackStackEntry(
            activityClass: $intent->activityClass,
            extras: $this->extractExtras($intent),
        );

        $this->backStack->push($chatId, $entry, clear: $clear);
    }

    private function instantiate(string $activityClass): BotActivity
    {
        /** @var BotActivity $activity */
        $activity = $this->container->get($activityClass);

        return $activity;
    }

    private function extractExtras(Intent $intent): array
    {
        return $intent->getExtras();
    }

    /**
     * Activities using the HasViewModel trait get their StateStore
     * injected transparently right after instantiation — Activity
     * code never has to know the store exists.
     */
    private function injectStateStore(BotActivity $activity): void
    {
        if ($this->stateStore !== null && method_exists($activity, 'bindStateStore')) {
            $activity->bindStateStore($this->stateStore);
        }
    }

    /**
     * If the Activity resolved (and mutated) a ViewModel during this
     * lifecycle call, persist it back to the StateStore so the next
     * request/Activity in the same conversation can rehydrate it.
     */
    private function persistViewModel(BotActivity $activity): void
    {
        if (method_exists($activity, 'persistViewModel')) {
            $activity->persistViewModel();
        }
    }

    /**
     * Pops the current Activity, restores the previous one from the
     * back-stack and re-runs its onResume() — the "virtual Back button".
     *
     * $finishingIntent identifies which Activity is asking to finish.
     * If it isn't actually the top of the stack (e.g. an Activity
     * that finished immediately from onCreate() without ever being
     * pushed — never resumed, so never persisted), nothing is popped;
     * we simply resume whatever was already on top.
     */
    public function goBack(Update $update, ?Intent $finishingIntent = null): ?View
    {
        $top = $this->backStack->current($update->chatId);

        if ($finishingIntent === null || ($top !== null && $top->activityClass === $finishingIntent->activityClass)) {
            $this->backStack->pop($update->chatId);
        }

        $previous = $this->backStack->current($update->chatId);

        if ($previous === null) {
            return null;
        }

        $activity = $this->instantiate($previous->activityClass);
        $activity->bindUpdate($update);
        $this->injectStateStore($activity);
        $activity->onResume();
        $this->persistViewModel($activity);

        return $activity->getContentView();
    }
}
