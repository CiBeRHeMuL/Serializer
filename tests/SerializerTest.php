<?php

namespace AndrewGos\Serializer\Tests;

use AndrewGos\Serializer\Encoder\JsonEncoder;
use AndrewGos\Serializer\Serializer;
use AndrewGos\Serializer\Tests\TestCase\BaseTestClass;
use AndrewGos\Serializer\Tests\TestCase\ChildTestClass;
use AndrewGos\Serializer\Tests\TestCase\ImplementingTestClass;
use AndrewGos\Serializer\Tests\TestCase\Person;
use AndrewGos\Serializer\Tests\TestCase\TestInterfaceForSerialization;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    private Serializer $serializer;

    public function testSerializeWithDefaultJsonEncoder(): void
    {
        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $this->serializer->addNormalizer('scalar', fn(mixed $value): mixed => $value);

        $result = $this->serializer->serialize(42, 'json');

        $this->assertSame('42', $result);
    }

    public function testSerializeWithCustomEncoder(): void
    {
        $this->serializer->addEncoder(
            'xml',
            fn(mixed $value): string => is_array($value)
                ? '<root>' . implode('', array_map(fn($v) => "<item>$v</item>", $value)) . '</root>'
                : "<root>$value</root>",
        );

        $this->serializer->addNormalizer('array', fn(mixed $value): array => $value);

        $result = $this->serializer->serialize(['foo', 'bar'], 'xml');

        $this->assertSame('<root><item>foo</item><item>bar</item></root>', $result);
    }

    public function testSerializeWithObjectNormalizer(): void
    {
        $person = new Person('John');
        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $this->serializer->addNormalizer(
            $person::class,
            function (Person $obj) {
                return [
                    'name' => $obj->getName(),
                ];
            },
        );

        $result = $this->serializer->serialize($person, 'json');

        $expected = '{"name":"John"}';
        $this->assertSame($expected, $result);
    }

    public function testSerializeWithWildcardNormalizer(): void
    {
        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $this->serializer->addNormalizer('*', fn(mixed $value): string => 'wildcard');

        $result = $this->serializer->serialize(new \stdClass(), 'json');

        $this->assertSame('"wildcard"', $result);
    }

    public function testAddNormalizers(): void
    {
        $normalizers = [
            'string' => fn(mixed $value): string => "string:$value",
            'int' => fn(mixed $value): string => "int:$value",
        ];

        $this->serializer->addNormalizers($normalizers);

        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $resultString = $this->serializer->serialize('hello', 'json');
        $resultInt = $this->serializer->serialize(42, 'json');

        $this->assertSame('"string:hello"', $resultString);
        $this->assertSame('"int:42"', $resultInt);
    }

    public function testAddEncoders(): void
    {
        $encoders = [
            'json' => (new JsonEncoder())(...),
            'custom' => fn(mixed $value): string => "custom:$value",
        ];

        $this->serializer->addEncoders($encoders);
        $this->serializer->addNormalizer('scalar', fn(mixed $value): mixed => $value);

        $resultJson = $this->serializer->serialize('hello', 'json');
        $resultCustom = $this->serializer->serialize('hello', 'custom');

        $this->assertSame('"hello"', $resultJson);
        $this->assertSame('custom:hello', $resultCustom);
    }

    public function testClassHierarchyNormalization(): void
    {
        $baseClass = new BaseTestClass();
        $childClass = new ChildTestClass();
        $implementingClass = new ImplementingTestClass();

        $this->serializer->addNormalizer(BaseTestClass::class, fn($obj) => ['type' => 'base']);
        $this->serializer->addNormalizer(TestInterfaceForSerialization::class, fn($obj) => ['type' => 'interface']);

        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $baseResult = $this->serializer->serialize($baseClass, 'json');
        $this->assertSame('{"type":"base"}', $baseResult);

        $childResult = $this->serializer->serialize($childClass, 'json');
        $this->assertSame('{"type":"base"}', $childResult);

        $interfaceResult = $this->serializer->serialize($implementingClass, 'json');
        $this->assertSame('{"type":"interface"}', $interfaceResult);
    }

    public function testMethodChaining(): void
    {
        $result = $this->serializer
            ->addNormalizer('string', fn(mixed $value): string => $value)
            ->addEncoder('json', (new JsonEncoder())(...));

        $this->assertSame($this->serializer, $result);
    }

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }
}
