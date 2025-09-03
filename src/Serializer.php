<?php

namespace AndrewGos\Serializer;

use AndrewGos\Serializer\Container\EncoderContainer;
use AndrewGos\Serializer\Container\NormalizerContainer;
use Closure;
use Psr\Container\ContainerInterface;

class Serializer implements SerializerInterface
{
    private ContainerInterface $encodersContainer;
    private ContainerInterface $normalizersContainer;

    public function __construct()
    {
        $this->encodersContainer = new EncoderContainer();
        $this->normalizersContainer = new NormalizerContainer();
    }

    /**
     * @param string $type
     * @param Closure(mixed): (array|string|float|int|bool|null) $normalizer
     *
     * @return $this
     */
    public function addNormalizer(string $type, Closure $normalizer): self
    {
        $this->normalizersContainer->addNormalizer($type, $normalizer);
        return $this;
    }

    /**
     * @param array<string, Closure(mixed): (array|string|float|int|bool|null)> $normalizers
     *
     * @return self
     */
    public function addNormalizers(array $normalizers): self
    {
        $this->normalizersContainer->addNormalizers($normalizers);
        return $this;
    }

    /**
     * @param string $format
     * @param Closure(array|string|float|int|bool|null): string $encoder
     *
     * @return self
     */
    public function addEncoder(string $format, Closure $encoder): self
    {
        $this->encodersContainer->addEncoder($format, $encoder);
        return $this;
    }

    /**
     * @param array<string, Closure(array|string|float|int|bool|null): string> $encoders
     *
     * @return self
     */
    public function addEncoders(array $encoders): self
    {
        $this->encodersContainer->addEncoders($encoders);
        return $this;
    }

    public function serialize(mixed $data, string $format): string
    {
        return $this->encode($this->normalize($data), $format);
    }

    public function normalize(mixed $data): array|string|float|int|bool|null
    {
        $normalizer = $this->normalizersContainer->get(get_debug_type($data));
        return $normalizer($data);
    }

    public function encode(float|int|bool|array|string|null $data, string $format): string
    {
        $encoder = $this->encodersContainer->get($format);
        return $encoder($data);
    }
}
