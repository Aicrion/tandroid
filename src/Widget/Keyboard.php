<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Fluent builder for inline or reply keyboards. Rows are composed
 * top-to-bottom, buttons left-to-right, mirroring a LinearLayout
 * with vertical orientation nesting horizontal rows.
 *
 * Inline and Reply keyboards render very differently on the wire,
 * because Telegram itself treats them very differently:
 *
 *   - An Inline button's tap comes back as a callback_query carrying
 *     that exact button's `callback_data` — so `Button::action()`
 *     can encode the target Activity directly on the button, and
 *     IntentResolver decodes it straight from the update.
 *   - A Reply button's tap comes back as an ordinary text Message
 *     whose text is just the button's label — Telegram does not
 *     support `callback_data` (or `url`) on Reply buttons at all.
 *
 * To let `Button::action()`/`Button::actionReplace()` work on *both*
 * kinds of keyboard with the same call, a Reply keyboard's action
 * buttons are rendered as plain-text buttons (just `text`), and
 * their intent payload is exposed separately via `reply_actions` —
 * Kernel persists that map into ReplyActionStore right before
 * sending, so the next plain-text update carrying that exact label
 * can still be resolved back into the same explicit Intent. See
 * ReplyActionStore's docblock for the full mechanism.
 */
final class Keyboard implements WidgetInterface
{
    /** @var list<list<Button>> */
    private array $rows = [];

    private bool $resizeKeyboard = true;

    private function __construct(
        public readonly bool $inline,
    ) {}

    public static function inline(): self
    {
        return new self(inline: true);
    }

    /**
     * @param bool $resizeKeyboard Telegram's `resize_keyboard` flag —
     *     true (the default) asks clients to shrink the keyboard to
     *     fit its buttons instead of using full-size default keys.
     *     Pass false to keep Telegram's own default (large buttons).
     */
    public static function reply(bool $resizeKeyboard = true): self
    {
        $keyboard = new self(inline: false);
        $keyboard->resizeKeyboard = $resizeKeyboard;

        return $keyboard;
    }

    public function row(Button ...$buttons): self
    {
        $clone = clone $this;
        $clone->rows[] = array_values($buttons);

        return $clone;
    }

    /** Overrides `resize_keyboard` on an already-built Reply keyboard. Has no effect on an Inline keyboard. */
    public function resizeKeyboard(bool $value = true): self
    {
        $clone = clone $this;
        $clone->resizeKeyboard = $value;

        return $clone;
    }

    public static function requestContact(string $label = 'ارسال شماره تماس'): self
    {
        return self::reply()->row(Button::requestContact($label));
    }

    public function render(): array
    {
        /** @var array<string, array{a: string, p: array, f: list<string>}> $replyActions label => intent payload, Reply keyboards only */
        $replyActions = [];
        $inline = $this->inline;

        $matrix = array_map(
            static function (array $row) use ($inline, &$replyActions): array {
                return array_map(
                    static function (Button $button) use ($inline, &$replyActions): array {
                        if (!$inline && $button->intentPayload !== null) {
                            $replyActions[$button->label] = $button->intentPayload;
                        }

                        if ($inline) {
                            return array_filter([
                                'text' => $button->label,
                                'callback_data' => $button->resolveCallbackData(),
                                'url' => $button->url,
                            ], static fn ($v) => $v !== null);
                        }

                        // Reply keyboard buttons only support `text` and the
                        // various `request_*` fields — `callback_data` and
                        // `url` are Inline-only and Telegram rejects them
                        // (or silently drops the button) if sent here.
                        return array_filter([
                            'text' => $button->label,
                            'request_contact' => $button->requestContact ?: null,
                        ], static fn ($v) => $v !== null);
                    },
                    $row,
                );
            },
            $this->rows,
        );

        $key = $this->inline ? 'inline_keyboard' : 'keyboard';
        $markup = [$key => $matrix];

        if (!$this->inline) {
            $markup['resize_keyboard'] = $this->resizeKeyboard;
        }

        $rendered = ['reply_markup' => $markup];

        if (!$this->inline) {
            $rendered['reply_actions'] = $replyActions;
        }

        return $rendered;
    }
}
