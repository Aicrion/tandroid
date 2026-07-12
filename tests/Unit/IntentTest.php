<?php
declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Intent\IntentFlag;
use PHPUnit\Framework\TestCase;

final class IntentTest extends TestCase
{
    public function test_it_builds_intent_with_extras_and_flags(): void
    {
        $intent = Intent::to(\stdClass::class)->putExtra('id', 123)->withFlag(IntentFlag::SingleTop);
        $this->assertTrue($intent->isExplicit());
        $this->assertSame(123, $intent->getExtra('id'));
        $this->assertTrue($intent->hasFlag(IntentFlag::SingleTop));
    }
}