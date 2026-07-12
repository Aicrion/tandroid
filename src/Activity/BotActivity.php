<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Activity;

use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\View\View;

/**
 * Base class for every screen/feature of the bot. Directly mirrors
 * android.app.Activity's lifecycle: the ActivityManager calls these
 * hooks in order as a user's conversation state changes.
 *
 *   onCreate  -> first entry, receives the triggering Intent
 *   onResume  -> called every time this Activity becomes the active
 *                one again (e.g. user returns via Back)
 *   onPause   -> called right before another Activity is pushed on top
 *   onDestroy -> called when this Activity is popped off the stack
 */
abstract class BotActivity
{
    protected ?View $contentView = null;

    protected Update $update;

    public function bindUpdate(Update $update): void
    {
        $this->update = $update;
    }

    /**
     * First entry point for this Activity. Return null to render
     * getContentView() as-is, or return the result of
     * startActivity()/finishWithResult() to have the ActivityManager
     * transparently continue the chain (push another Activity, or
     * pop back) within the very same incoming Update — no extra
     * round-trip to Telegram required.
     */
    abstract public function onCreate(Intent $intent): ?NavigationRequest;

    public function onResume(): ?NavigationRequest
    {
        return null;
    }

    public function onPause(): void {}

    public function onDestroy(): void {}

    /**
     * Called when this Activity receives a callback_query while
     * already active on the stack (e.g. checkbox toggle), without a
     * full onCreate cycle. Equivalent to onNewIntent() in Android.
     */
    public function onNewIntent(Intent $intent): ?NavigationRequest
    {
        return null;
    }

    protected function setContentView(View $view): void
    {
        $this->contentView = $view;
    }

    public function getContentView(): ?View
    {
        return $this->contentView;
    }

    protected function update(): Update
    {
        return $this->update;
    }

    /**
     * Requests navigation to another Activity. The ActivityManager
     * intercepts this call — it does not render or dispatch directly.
     */
    protected function startActivity(Intent $intent): NavigationRequest
    {
        return NavigationRequest::navigate($intent);
    }

    /**
     * Pops the current Activity off the back-stack, returning control
     * (and optionally a result payload) to the previous one.
     */
    protected function finishWithResult(array $result = []): NavigationRequest
    {
        return NavigationRequest::finish($result);
    }
}