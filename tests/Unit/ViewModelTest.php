<?php
declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Kernel\ViewModel\ViewModel;
use PHPUnit\Framework\TestCase;

final class ViewModelTest extends TestCase
{
    public function test_it_hydrates_and_dehydrates_state(): void
    {
        $vm = new class extends ViewModel { public function put(string $k, mixed $v): void { $this->set($k, $v); } public function getv(string $k): mixed { return $this->get($k); } };
        $vm->hydrate(['a' => 1]);
        $this->assertSame(1, $vm->getv('a'));
        $vm->put('b', 2);
        $this->assertSame(['a' => 1, 'b' => 2], $vm->dehydrate());
    }
}