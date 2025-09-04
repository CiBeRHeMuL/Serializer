<?php

namespace AndrewGos\Serializer;

use AndrewGos\Serializer\Encoder\JsonEncoder;
use AndrewGos\Serializer\Encoder\XmlEncoder;
use AndrewGos\Serializer\Normalizer\ArrayNormalizer;
use AndrewGos\Serializer\Normalizer\ScalarNormalizer;

final class SerializerFactory
{
    public static function getDefaultSerializer(): Serializer
    {
        $serializer = new Serializer();
        $serializer->addNormalizers([
            'scalar' => (new ScalarNormalizer())(...),
            '*' => fn(mixed $value) => $value,
            'object' => fn(object $value) => $serializer->normalize((array)$value),
            'array' => (new ArrayNormalizer($serializer))(...),
        ]);
        $serializer->addEncoders([
            'json' => (new JsonEncoder())(...),
            'xml' => (new XmlEncoder())(...),
        ]);
        return $serializer;
    }
}
