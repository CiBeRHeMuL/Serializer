<?php

namespace AndrewGos\Serializer\Normalizer;

use AndrewGos\Serializer\SerializerInterface;

final class ArrayNormalizer
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(mixed $value): string|float|int|bool|null
    {
        array_walk(
            $value,
            $this->serializer->normalize(...),
        );
        return $value;
    }
}
