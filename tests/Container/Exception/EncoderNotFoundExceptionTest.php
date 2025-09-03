<?php

namespace AndrewGos\Serializer\Tests\Container\Exception;

use AndrewGos\Serializer\Container\Exception\EncoderNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class EncoderNotFoundExceptionTest extends TestCase
{
    public function testNew(): void
    {
        $exception = EncoderNotFoundException::new('test_format');

        $this->assertSame('Encoder for type "test_format" not found', $exception->getMessage());
    }
}
