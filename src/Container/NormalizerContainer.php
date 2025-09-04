<?php

namespace AndrewGos\Serializer\Container;

use AndrewGos\Serializer\Container\Exception\InvalidTypeException;
use AndrewGos\Serializer\Container\Exception\NormalizerNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;

final class NormalizerContainer implements ContainerInterface
{
    public const SCALAR_TYPES = [
        'string',
        'float',
        'int',
        'bool',
        'null',
    ];

    final public const SCALAR_TYPES_REGEX = '^(?:string|float|int|bool|scalar)$';
    final public const NULL_TYPE_REGEX = '^null$';
    final public const ARRAY_TYPE_REGEX = '^array$';
    final public const USER_TYPE_REGEX = '^\\\\?(?:[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\\\\)*[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$';
    final public const WILDCARD_TYPE_REGEX = '^\\*$';

    /**
     * A map of type names to their corresponding normalizer functions.
     * Each normalizer is a Closure that converts a value of specific type to a primitive value.
     * The key is a string representing the type name, and the value is a normalizer function
     * that returns either an array or a scalar value (string, float, int, bool, or null).
     *
     * @var array<string, Closure(mixed): (object|array|string|float|int|bool|null)>
     */
    private array $normalizers = [];
    private string $typeRegex;

    public function __construct()
    {
        $this->typeRegex = $this->buildTypeRegex();
    }

    /**
     * @inheritDoc
     *
     * @return Closure(mixed): (object|array|string|float|int|bool|null)
     */
    public function get(string $id): Closure
    {
        $normalizer = $this->tryFind($id);
        if ($normalizer === null) {
            throw NormalizerNotFoundException::new($id);
        }
        return $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->tryFind($id) !== null;
    }

    /**
     * @param string $type
     * @param Closure(mixed): (object|array|string|float|int|bool|null) $normalizer
     *
     * @return $this
     */
    public function addNormalizer(string $type, Closure $normalizer): self
    {
        if (!preg_match($this->typeRegex, $type)) {
            throw new InvalidTypeException(
                sprintf(
                    'Cannot add normalizer for type %s: type must be a valid user type, builtin type, "scalar" or "*"',
                    $type,
                ),
            );
        }

        $this->normalizers[$type] = $normalizer;
        return $this;
    }

    /**
     * @param array<string, Closure(mixed): (object|array|string|float|int|bool|null)> $normalizers
     *
     * @return self
     */
    public function addNormalizers(array $normalizers): self
    {
        foreach ($normalizers as $type => $normalizer) {
            $this->addNormalizer($type, $normalizer);
        }
        return $this;
    }

    private function tryFind(string $type): Closure|null
    {
        // Check for a direct match or a cached result.
        if (isset($this->normalizers[$type])) {
            return $this->normalizers[$type];
        }

        $normalizer = null;

        // Handle scalar types (NULL TOO!!!).
        if (in_array($type, self::SCALAR_TYPES, true)) {
            $normalizer = $this->normalizers['scalar'] ?? null;
        } elseif (class_exists($type) || interface_exists($type)) {
            // `class_parents` returns parents in order from nearest to farthest.
            // So, the first match will be the most specific one.
            $parents = class_parents($type);
            foreach ($parents as $parent) {
                if (isset($this->normalizers[$parent])) {
                    $normalizer = $this->normalizers[$parent];
                    break;
                }
            }

            // If no normalizer is found for parent classes, check implemented interfaces.
            if ($normalizer === null) {
                $interfaces = class_implements($type);
                foreach ($interfaces as $interface) {
                    if (isset($this->normalizers[$interface])) {
                        $normalizer = $this->normalizers[$interface];
                        break;
                    }
                }
            }
        }

        // Fallback to the wildcard normalizer if no specific one is found.
        $normalizer ??= $this->normalizers['*'] ?? null;

        if ($normalizer === null) {
            return null;
        }

        // Cache the found normalizer for the given type to speed up future lookups.
        $this->normalizers[$type] = $normalizer;
        return $normalizer;
    }

    private function buildTypeRegex(): string
    {
        return '/(?:'
            . implode(
                ')|(?:',
                [
                    self::SCALAR_TYPES_REGEX,
                    self::NULL_TYPE_REGEX,
                    self::ARRAY_TYPE_REGEX,
                    self::USER_TYPE_REGEX,
                    self::WILDCARD_TYPE_REGEX,
                ],
            )
            . ')/ui';
    }
}
