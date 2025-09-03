<?php

namespace AndrewGos\Serializer\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use UnexpectedValueException;

class NormalizerNotFoundException extends UnexpectedValueException implements NotFoundExceptionInterface
{
    public static function new(string $type): self
    {
        return new self("Normalizer for type $type not found");
    }
}
