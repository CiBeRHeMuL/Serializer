<?php

namespace AndrewGos\Serializer\Tests\Normalizer;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionObject;

class ObjectNormalizerTest extends TestCase
{
    public function testPublicPropertiesNormalizer(): void
    {
        $normalizer = function ($object) {
            $data = [];
            $reflection = new ReflectionObject($object);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $name = $property->getName();
                $data[$name] = $property->getValue($object);
            }

            return $data;
        };

        $object = new class() {
            public string $name = 'John';
            public int $age = 30;
            private string $secret = 'hidden';
        };

        $result = $normalizer($object);

        $this->assertSame(
            [
                'name' => 'John',
                'age' => 30,
            ],
            $result,
        );
        $this->assertArrayNotHasKey('secret', $result);
    }

    public function testGetterMethodsNormalizer(): void
    {
        $normalizer = function ($object) {
            $data = [];
            $reflection = new ReflectionObject($object);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $name = $method->getName();

                // Ищем только геттеры
                if (str_starts_with($name, 'get') && $method->getNumberOfParameters() === 0) {
                    $propertyName = lcfirst(substr($name, 3));
                    $data[$propertyName] = $method->invoke($object);
                } elseif (str_starts_with($name, 'is') && $method->getNumberOfParameters() === 0) {
                    $propertyName = lcfirst(substr($name, 2));
                    $data[$propertyName] = $method->invoke($object);
                }
            }

            return $data;
        };

        $object = new class() {
            private string $name = 'John';
            private int $age = 30;
            private bool $active = true;

            public function getName(): string
            {
                return $this->name;
            }

            public function getAge(): int
            {
                return $this->age;
            }

            public function isActive(): bool
            {
                return $this->active;
            }

            public function doSomething(): void
            {
            }
        };

        $result = $normalizer($object);

        $this->assertSame(
            [
                'name' => 'John',
                'age' => 30,
                'active' => true,
            ],
            $result,
        );
        $this->assertArrayNotHasKey('doSomething', $result);
    }

    public function testRecursiveNormalizer(): void
    {
        $normalizer = function ($object, $depth = 3) use (&$normalizer) {
            if ($depth <= 0) {
                return '[MAX DEPTH REACHED]';
            }

            $data = [];
            $reflection = new ReflectionObject($object);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $name = $property->getName();
                $value = $property->getValue($object);

                if (is_object($value)) {
                    $data[$name] = $normalizer($value, $depth - 1);
                } elseif (is_array($value)) {
                    $data[$name] = array_map(
                        function ($item) use ($normalizer, $depth) {
                            return is_object($item) ? $normalizer($item, $depth - 1) : $item;
                        },
                        $value,
                    );
                } else {
                    $data[$name] = $value;
                }
            }

            return $data;
        };

        // Создаем сложный объект с вложенностью
        $address = new class() {
            public string $street = '123 Main St';
            public string $city = 'New York';
        };

        $user = new class() {
            public string $name = 'John';
            public object $address;
            public array $roles = ['admin', 'editor'];
        };
        $user->address = $address;

        $company = new class() {
            public string $name = 'ACME Inc.';
            public object $mainContact;
        };
        $company->mainContact = $user;

        // Тестируем нормализацию с ограничением глубины
        $result = $normalizer($company);

        $this->assertSame(
            [
                'name' => 'ACME Inc.',
                'mainContact' => [
                    'name' => 'John',
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'New York',
                    ],
                    'roles' => ['admin', 'editor'],
                ],
            ],
            $result,
        );

        // Тестируем с малой глубиной
        $result = $normalizer($company, 1);
        $this->assertSame(
            [
                'name' => 'ACME Inc.',
                'mainContact' => '[MAX DEPTH REACHED]',
            ],
            $result,
        );
    }
}
