<?php

namespace Tests\Unit;

use App\Exceptions\DocumentParseException;
use PHPUnit\Framework\TestCase;

class DocumentParseExceptionTest extends TestCase
{
    public function test_hint_is_stored(): void
    {
        $exception = new DocumentParseException('Failed', 'PDF tool missing');
        $this->assertSame('PDF tool missing', $exception->getHint());
    }
}
