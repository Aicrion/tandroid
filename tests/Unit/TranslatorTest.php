<?php
declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    public function test_it_resolves_closure_translation(): void
    {
        $t = Translator::create(__DIR__ . '/../../lang', 'fa');
        $this->assertSame('یک سفارش با موفقیت ثبت شد ✅', $t->trans('order.created', [1], 'fa'));
    }
}
