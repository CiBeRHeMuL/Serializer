<?php

namespace AndrewGos\Serializer\Tests\Container;

use AndrewGos\Serializer\Container\Exception\InvalidTypeException;
use AndrewGos\Serializer\Container\Exception\NormalizerNotFoundException;
use AndrewGos\Serializer\Container\NormalizerContainer;
use AndrewGos\Serializer\Tests\TestCase\BIEnum;
use AndrewGos\Serializer\Tests\TestCase\BSEnum;
use AndrewGos\Serializer\Tests\TestCase\ChildClass;
use AndrewGos\Serializer\Tests\TestCase\ImplementingClass;
use AndrewGos\Serializer\Tests\TestCase\ParentClass;
use AndrewGos\Serializer\Tests\TestCase\TestInterface;
use AndrewGos\Serializer\Tests\TestCase\UEnum;
use BackedEnum;
use PHPUnit\Framework\Attributes\Requires;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use UnitEnum;

class NormalizerContainerTest extends TestCase
{
    private NormalizerContainer $container;

    public function testAddNormalizerForScalarType(): void
    {
        $normalizer = fn(mixed $value): string => (string)$value;

        $this->container->addNormalizer('string', $normalizer);

        $this->assertTrue($this->container->has('string'));
        $this->assertSame($normalizer, $this->container->get('string'));
    }

    public function testAddNormalizerForNullType(): void
    {
        $normalizer = fn(mixed $value): ?string => null;

        $this->container->addNormalizer('null', $normalizer);

        $this->assertTrue($this->container->has('null'));
        $this->assertSame($normalizer, $this->container->get('null'));
    }

    public function testAddNormalizerForArrayType(): void
    {
        $normalizer = fn(mixed $value): array => (array)$value;

        $this->container->addNormalizer('array', $normalizer);

        $this->assertTrue($this->container->has('array'));
        $this->assertSame($normalizer, $this->container->get('array'));
    }

    public function testAddNormalizerForUserType(): void
    {
        $normalizer = fn(mixed $value): array => ['class' => get_class($value)];

        $this->container->addNormalizer(\stdClass::class, $normalizer);

        $this->assertTrue($this->container->has(\stdClass::class));
        $this->assertSame($normalizer, $this->container->get(\stdClass::class));
    }

    public function testAddNormalizerForWildcardType(): void
    {
        $normalizer = fn(mixed $value): string => 'wildcard';

        $this->container->addNormalizer('*', $normalizer);

        $this->assertTrue($this->container->has('*'));
        $this->assertSame($normalizer, $this->container->get('*'));
    }

    public function testAddNormalizerWithInvalidType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->container->addNormalizer('invalid type!', fn() => null);
    }

    public function testAddNormalizers(): void
    {
        $normalizers = [
            'string' => fn(mixed $value): string => (string)$value,
            'int' => fn(mixed $value): int => (int)$value,
            'array' => fn(mixed $value): array => (array)$value,
        ];

        $this->container->addNormalizers($normalizers);

        $this->assertTrue($this->container->has('string'));
        $this->assertTrue($this->container->has('int'));
        $this->assertTrue($this->container->has('array'));
        $this->assertSame($normalizers['string'], $this->container->get('string'));
        $this->assertSame($normalizers['int'], $this->container->get('int'));
        $this->assertSame($normalizers['array'], $this->container->get('array'));
    }

    public function testHasReturnsFalseForNonExistentNormalizer(): void
    {
        $this->assertFalse($this->container->has('non_existent'));
    }

    public function testGetThrowsExceptionForNonExistentNormalizer(): void
    {
        $this->expectException(NormalizerNotFoundException::class);
        $this->container->get('non_existent');
    }

    public function testScalarTypeNormalization(): void
    {
        $scalarNormalizer = fn(mixed $value): string => 'scalar:' . $value;
        $this->container->addNormalizer('scalar', $scalarNormalizer);

        $this->assertTrue($this->container->has('string'));
        $this->assertTrue($this->container->has('int'));
        $this->assertTrue($this->container->has('float'));
        $this->assertTrue($this->container->has('bool'));

        $result = ($this->container->get('int'))(42);
        $this->assertSame('scalar:42', $result);
    }

    public function testWildcardNormalization(): void
    {
        $wildcard = fn(mixed $value): string => 'wildcard';
        $this->container->addNormalizer('*', $wildcard);

        $this->assertTrue($this->container->has('non_existent'));
        $result = ($this->container->get('non_existent'))('anything');
        $this->assertSame('wildcard', $result);
    }

    #[Requires('PHP', '>=8.0')]
    public function testClassHierarchyNormalization(): void
    {
        // Подготовим тестовые классы
        $baseNormalizer = fn(mixed $value): array => ['type' => 'base'];
        $childNormalizer = fn(mixed $value): array => ['type' => 'child'];
        $interfaceNormalizer = fn(mixed $value): array => ['type' => 'interface'];

        // Зарегистрируем нормализаторы
        $this->container->addNormalizer(ParentClass::class, $baseNormalizer);
        $this->container->addNormalizer(ChildClass::class, $childNormalizer);
        $this->container->addNormalizer(TestInterface::class, $interfaceNormalizer);

        // Проверим поиск точного совпадения
        $this->assertSame($childNormalizer, $this->container->get(ChildClass::class));

        // Проверим поиск по родительскому классу (если не зарегистрирован дочерний)
        $this->container = new NormalizerContainer();
        $this->container->addNormalizer(ParentClass::class, $baseNormalizer);
        $this->assertTrue($this->container->has(ChildClass::class));
        $this->assertSame($baseNormalizer, $this->container->get(ChildClass::class));

        // Проверим поиск по интерфейсу
        $this->container = new NormalizerContainer();
        $this->container->addNormalizer(TestInterface::class, $interfaceNormalizer);
        $this->assertTrue($this->container->has(ImplementingClass::class));
        $this->assertSame($interfaceNormalizer, $this->container->get(ImplementingClass::class));

        // Проверим кэширование результата поиска
        $this->container = new NormalizerContainer();
        $this->container->addNormalizer(ParentClass::class, $baseNormalizer);
        $this->container->get(ChildClass::class); // Первый вызов для кэширования
        $this->assertTrue($this->container->has(ChildClass::class));
    }

    public function testMethodChaining(): void
    {
        $result = $this->container
            ->addNormalizer('string', fn(mixed $value): string => (string)$value)
            ->addNormalizers([
                'int' => fn(mixed $value): int => (int)$value,
                'float' => fn(mixed $value): float => (float)$value,
            ]);

        $this->assertSame($this->container, $result);
        $this->assertTrue($this->container->has('string'));
        $this->assertTrue($this->container->has('int'));
        $this->assertTrue($this->container->has('float'));
    }

    public function testEnumNormalizers(): void
    {
        $unitNormalizer = fn(UnitEnum $e): string => $e->name;
        $backedNormalizer = fn(BackedEnum $e): string|int => $e->value;

        $this->container->addNormalizers([
            UnitEnum::class => $unitNormalizer,
            BackedEnum::class => $backedNormalizer,
        ]);

        $this->assertTrue($this->container->has(UnitEnum::class));
        $this->assertTrue($this->container->has(BackedEnum::class));
        $this->assertTrue($this->container->has(UEnum::class));
        $this->assertTrue($this->container->has(BSEnum::class));
        $this->assertTrue($this->container->has(BIEnum::class));
        $this->assertSame($unitNormalizer, $this->container->get(UEnum::class));
        $this->assertSame($backedNormalizer, $this->container->get(BSEnum::class));
        $this->assertSame($backedNormalizer, $this->container->get(BIEnum::class));

        $this->assertSame(UEnum::A->name, $this->container->get(UEnum::class)(UEnum::A));
        $this->assertSame(BSEnum::A->value, $this->container->get(BSEnum::class)(BSEnum::A));
        $this->assertSame(BIEnum::A->value, $this->container->get(BIEnum::class)(BIEnum::A));
    }

    protected function setUp(): void
    {
        $this->container = new NormalizerContainer();
    }
}
