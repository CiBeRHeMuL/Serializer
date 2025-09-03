<?php

namespace AndrewGos\Serializer;

interface SerializerInterface
{
    /**
     * @param mixed $data
     * @param string $format
     *
     * @return string
     */
    public function serialize(mixed $data, string $format): string;

    /**
     * @param mixed $data
     *
     * @return array|string|float|int|bool|null
     */
    public function normalize(mixed $data): array|string|float|int|bool|null;

    /**
     * @param array|string|float|int|bool|null $data
     * @param string $format
     *
     * @return string
     */
    public function encode(array|string|float|int|bool|null $data, string $format): string;
}
