<?php

namespace AndrewGos\Serializer\Normalizer;

use AndrewGos\Serializer\Normalizer\Exception\NormalizationFailedException;

final class ScalarNormalizer
{
    public function __invoke(mixed $value): string|float|int|bool|null
    {
        if (is_scalar($value) || is_null($value)) {
            return $value;
        }
        throw NormalizationFailedException::new(get_debug_type($value), $this(...));
    }
}
