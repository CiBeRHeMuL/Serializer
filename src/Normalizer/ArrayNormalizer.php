<?php

namespace AndrewGos\Serializer\Normalizer;

use AndrewGos\Serializer\SerializerInterface;

final class ArrayNormalizer
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(mixed $value): array
    {
        array_walk(
            $value,
            fn(&$value) => $value = $this->serializer->normalize($value),
        );
        return $value;
    }
}
