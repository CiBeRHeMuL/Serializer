<?php

namespace AndrewGos\Serializer\Tests\Integration;

use AndrewGos\Serializer\Encoder\JsonEncoder;
use AndrewGos\Serializer\Serializer;
use AndrewGos\Serializer\Tests\TestCase\Address;
use AndrewGos\Serializer\Tests\TestCase\Customer;
use AndrewGos\Serializer\Tests\TestCase\Employee;
use AndrewGos\Serializer\Tests\TestCase\Identifiable;
use AndrewGos\Serializer\Tests\TestCase\Person;
use AndrewGos\Serializer\Tests\TestCase\User;
use AndrewGos\Serializer\Tests\TestCase\UserProfile;
use PHPUnit\Framework\TestCase;

class SerializationIntegrationTest extends TestCase
{
    private Serializer $serializer;

    public function testSerializeScalars(): void
    {
        $this->assertSame('"hello"', $this->serializer->serialize('hello', 'json'));
        $this->assertSame('42', $this->serializer->serialize(42, 'json'));
        $this->assertSame('3.14', $this->serializer->serialize(3.14, 'json'));
        $this->assertSame('true', $this->serializer->serialize(true, 'json'));
        $this->assertSame('null', $this->serializer->serialize(null, 'json'));
    }

    public function testSerializeArrays(): void
    {
        $simpleArray = [1, 2, 3];
        $this->assertSame('[1,2,3]', $this->serializer->serialize($simpleArray, 'json'));

        $assocArray = ['name' => 'John', 'age' => 30];
        $this->assertSame('{"name":"John","age":30}', $this->serializer->serialize($assocArray, 'json'));

        $nestedArray = ['user' => ['name' => 'John', 'roles' => ['admin', 'editor']]];
        $this->assertSame(
            '{"user":{"name":"John","roles":["admin","editor"]}}',
            $this->serializer->serialize($nestedArray, 'json'),
        );
    }

    public function testSerializeObjects(): void
    {
        $user = new User(1, 'John Doe', 'john@example.com', true);
        $expected = '{"id":1,"name":"John Doe","email":"john@example.com","active":true}';

        $this->assertSame($expected, $this->serializer->serialize($user, 'json'));
    }

    public function testSerializeNestedObjects(): void
    {
        $user = new User(1, 'John Doe', 'john@example.com', true);
        $address = new Address('123 Main St', 'New York', '10001');
        $profile = new UserProfile($user, $address);

        $expected = '{"user":{"id":1,"name":"John Doe","email":"john@example.com","active":true},"address":{"street":"123 Main St","city":"New York","zipCode":"10001"}}';

        $this->assertSame($expected, $this->serializer->serialize($profile, 'json'));
    }

    public function testSerializeWithCustomFormat(): void
    {
        $xmlEncoder = function ($data) use (&$xmlEncoder): string {
            if (is_scalar($data) || is_null($data)) {
                return "<value>" . (string)$data . "</value>";
            }

            if (is_array($data)) {
                $result = "<root>";
                foreach ($data as $key => $value) {
                    if (is_int($key)) {
                        $result .= "<item>" . $xmlEncoder($value) . "</item>";
                    } else {
                        $result .= "<{$key}>" . $xmlEncoder($value) . "</{$key}>";
                    }
                }
                $result .= "</root>";
                return $result;
            }

            return "<error>Unsupported type</error>";
        };
        $this->serializer->addEncoder('xml', $xmlEncoder);

        $user = new User(1, 'John Doe', 'john@example.com', true);
        $result = $this->serializer->serialize($user, 'xml');

        $expected = '<root><id><value>1</value></id><name><value>John Doe</value></name><email><value>john@example.com</value></email><active><value>1</value></active></root>';
        $this->assertSame($expected, $result);
    }

    public function testInheritanceBasedNormalization(): void
    {
        $this->serializer->addNormalizer(
            Person::class,
            function (Person $person) {
                return [
                    'type' => 'person',
                    'name' => $person->getName(),
                ];
            },
        );

        $this->serializer->addNormalizer(
            Identifiable::class,
            function (Identifiable $entity) {
                return [
                    'type' => 'identifiable',
                    'id' => $entity->getId(),
                ];
            },
        );

        $person = new Person('John Doe');
        $employee = new Employee('Jane Smith', 'Developer');
        $customer = new Customer(42, 'Bob Johnson');

        $personResult = $this->serializer->serialize($person, 'json');
        $this->assertSame('{"type":"person","name":"John Doe"}', $personResult);

        $employeeResult = $this->serializer->serialize($employee, 'json');
        $this->assertSame('{"type":"person","name":"Jane Smith"}', $employeeResult);

        $customerResult = $this->serializer->serialize($customer, 'json');
        $this->assertSame('{"type":"identifiable","id":42}', $customerResult);
    }

    protected function setUp(): void
    {
        $this->serializer = new Serializer();

        $this->serializer->addEncoder('json', (new JsonEncoder())(...));

        $this->serializer->addNormalizers([
            'string' => fn(string $value): string => $value,
            'int' => fn(int $value): int => $value,
            'float' => fn(float $value): float => $value,
            'bool' => fn(bool $value): bool => $value,
            'null' => fn(mixed $value): ?string => null,
            'array' => fn(array $value): array => $value,
            User::class => function (User $user) {
                return [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'active' => $user->isActive(),
                ];
            },
            Address::class => function (Address $address) {
                return [
                    'street' => $address->getStreet(),
                    'city' => $address->getCity(),
                    'zipCode' => $address->getZipCode(),
                ];
            },
            UserProfile::class => function (UserProfile $profile) {
                return [
                    'user' => $this->serializer->normalize($profile->getUser()),
                    'address' => $this->serializer->normalize($profile->getAddress()),
                ];
            },
        ]);
    }
}
