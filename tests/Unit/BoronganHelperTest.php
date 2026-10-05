<?php

namespace Tests\Unit;

use App\Helpers\BoronganHelper;
use Tests\TestCase;

class BoronganHelperTest extends TestCase
{
    public function test_it_rounds_gram_values_using_half_up_rule(): void
    {
        $this->assertSame('157.472', BoronganHelper::formatGram(157471.98));
        $this->assertSame('157.471', BoronganHelper::formatGram(157471.49));
        $this->assertSame('13', BoronganHelper::formatGram(12.5));
    }
}
