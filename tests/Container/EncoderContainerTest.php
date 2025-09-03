<?php

namespace AndrewGos\Serializer\Tests\Container;

use AndrewGos\Serializer\Container\EncoderContainer;
use AndrewGos\Serializer\Container\Exception\EncoderNotFoundException;
use PHPUnit\Framework\TestCase;

class EncoderContainerTest extends TestCase
{
    private EncoderContainer $container;

    public function testAddEncoder(): void
    {
        $encoder = fn(mixed $value): string => 'encoded:' . json_encode($value);

        $this->container->addEncoder('test_format', $encoder);

        $this->assertTrue($this->container->has('test_format'));
        $this->assertSame($encoder, $this->container->get('test_format'));
    }

    public function testAddEncoders(): void
    {
        $encoders = [
            'format1' => fn(mixed $value): string => 'format1:' . json_encode($value),
            'format2' => fn(mixed $value): string => 'format2:' . json_encode($value),
        ];

        $this->container->addEncoders($encoders);

        $this->assertTrue($this->container->has('format1'));
        $this->assertTrue($this->container->has('format2'));
        $this->assertSame($encoders['format1'], $this->container->get('format1'));
        $this->assertSame($encoders['format2'], $this->container->get('format2'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->container->has('non_existent'));

        $this->container->addEncoder('existent', fn(mixed $value): string => (string)$value);

        $this->assertTrue($this->container->has('existent'));
        $this->assertFalse($this->container->has('non_existent'));
    }

    public function testGet(): void
    {
        $encoder = fn(mixed $value): string => 'test:' . json_encode($value);
        $this->container->addEncoder('format', $encoder);

        $result = $this->container->get('format');

        $this->assertSame($encoder, $result);
    }

    public function testGetThrowsExceptionForNonExistentEncoder(): void
    {
        $this->expectException(EncoderNotFoundException::class);
        $this->expectExceptionMessage('Encoder for type "non_existent" not found');

        $this->container->get('non_existent');
    }

    public function testEncoderFunctionality(): void
    {
        $jsonEncoder = fn(mixed $value): string => json_encode($value);
        $xmlEncoder =
            fn(mixed $value): string => "<root>"
                . (is_array($value) ? implode('', array_map(fn($v) => "<item>$v</item>", $value)) : $value)
                . "</root>";

        $this->container->addEncoder('json', $jsonEncoder);
        $this->container->addEncoder('xml', $xmlEncoder);

        $data = ['foo', 'bar'];

        $jsonResult = ($this->container->get('json'))($data);
        $xmlResult = ($this->container->get('xml'))($data);

        $this->assertSame('["foo","bar"]', $jsonResult);
        $this->assertSame('<root><item>foo</item><item>bar</item></root>', $xmlResult);
    }

    public function testMethodChaining(): void
    {
        $result = $this->container
            ->addEncoder('format1', fn(mixed $value): string => 'format1')
            ->addEncoders([
                'format2' => fn(mixed $value): string => 'format2',
                'format3' => fn(mixed $value): string => 'format3',
            ]);

        $this->assertSame($this->container, $result);
        $this->assertTrue($this->container->has('format1'));
        $this->assertTrue($this->container->has('format2'));
        $this->assertTrue($this->container->has('format3'));
    }

    protected function setUp(): void
    {
        $this->container = new EncoderContainer();
    }
}
