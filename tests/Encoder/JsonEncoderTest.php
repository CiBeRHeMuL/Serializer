<?php

namespace AndrewGos\Serializer\Tests\Encoder;

use AndrewGos\Serializer\Encoder\Exception\EncodingFailedException;
use AndrewGos\Serializer\Encoder\JsonEncoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonEncoderTest extends TestCase
{
    public static function provideEncodeCases(): array
    {
        return [
            'null' => [null, 'null'],
            'boolean true' => [true, 'true'],
            'boolean false' => [false, 'false'],
            'integer' => [42, '42'],
            'float' => [3.14, '3.14'],
            'float zero' => [0.0, '0.0'], // Проверка JSON_PRESERVE_ZERO_FRACTION
            'string' => ['hello', '"hello"'],
            'array of numbers' => [[1, 2, 3], '[1,2,3]'],
            'associative array' => [['foo' => 'bar'], '{"foo":"bar"}'],
            'nested array' => [['foo' => ['bar' => 'baz']], '{"foo":{"bar":"baz"}}'],
            'unicode string' => ['привет', '"привет"'], // Проверка JSON_UNESCAPED_UNICODE
        ];
    }

    #[DataProvider('provideEncodeCases')]
    public function testEncode(mixed $value, string $expected): void
    {
        $encoder = new JsonEncoder();
        $result = $encoder($value);
        $this->assertSame($expected, $result);
    }

    public function testCustomFlags(): void
    {
        $encoder = new JsonEncoder(flags: 0); // Без флагов
        $result = $encoder(0.0);
        $this->assertSame('0', $result); // Без JSON_PRESERVE_ZERO_FRACTION

        $encoder = new JsonEncoder(flags: JSON_NUMERIC_CHECK);
        $result = $encoder('42');
        $this->assertSame('42', $result); // Строки с числами конвертируются в числа
    }

    public function testCustomDepth(): void
    {
        // Создаем глубоко вложенный массив
        $deepArray = [];
        $current = &$deepArray;
        for ($i = 0; $i < 10; $i++) {
            $current['level'] = $i;
            $current['next'] = [];
            $current = &$current['next'];
        }

        // Стандартная глубина должна справиться
        $encoder = new JsonEncoder();
        $result = $encoder($deepArray);
        $this->assertNotFalse(json_decode($result));

        // Малая глубина должна вызвать ошибку
        $this->expectException(EncodingFailedException::class);
        $encoder = new JsonEncoder(depth: 3);
        $encoder($deepArray);
    }
}
