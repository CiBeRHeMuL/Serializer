<?php

namespace AndrewGos\Serializer\Container;

use AndrewGos\Serializer\Container\Exception\EncoderNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;

final class EncoderContainer implements ContainerInterface
{
    /**
     * @var array<string, Closure(object|array|string|float|int|bool|null): string>
     */
    private array $encoders = [];

    /**
     * @param string $id
     *
     * @return Closure(object|array|string|float|int|bool|null): string
     */
    public function get(string $id): Closure
    {
        return $this->encoders[$id] ?? throw EncoderNotFoundException::new($id);
    }

    public function has(string $id): bool
    {
        return isset($this->encoders[$id]);
    }

    /**
     * @param string $format
     * @param Closure(object|array|string|float|int|bool|null): string $encoder
     *
     * @return self
     */
    public function addEncoder(string $format, Closure $encoder): self
    {
        $this->encoders[$format] = $encoder;
        return $this;
    }

    /**
     * @param array<string, Closure(object|array|string|float|int|bool|null): string> $encoders
     *
     * @return self
     */
    public function addEncoders(array $encoders): self
    {
        foreach ($encoders as $format => $encoder) {
            $this->addEncoder($format, $encoder);
        }
        return $this;
    }
}
