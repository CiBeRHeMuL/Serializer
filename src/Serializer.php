<?php

namespace AndrewGos\Serializer;

use AndrewGos\Serializer\Container\EncoderContainer;
use AndrewGos\Serializer\Container\NormalizerContainer;
use AndrewGos\Serializer\Normalizer\ArrayNormalizer;
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
     * @param Closure(mixed): (object|array|string|float|int|bool|null) $normalizer
     *
     * @return $this
     */
    public function addNormalizer(string $type, Closure $normalizer): self
    {
        $this->normalizersContainer->addNormalizer($type, $normalizer);
        return $this;
    }

    /**
     * @param array<string, Closure(mixed): (object|array|string|float|int|bool|null)> $normalizers
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
     * @param Closure(object|array|string|float|int|bool|null): string $encoder
     *
     * @return self
     */
    public function addEncoder(string $format, Closure $encoder): self
    {
        $this->encodersContainer->addEncoder($format, $encoder);
        return $this;
    }

    /**
     * @param array<string, Closure(object|array|string|float|int|bool|null): string> $encoders
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

    public function normalize(mixed $data): object|array|string|float|int|bool|null
    {
        if (is_array($data)) {
            if ($this->normalizersContainer->has('array')) {
                $normalizer = $this->normalizersContainer->get('array');
                return $normalizer($data);
            } else {
                $arrayNormalizer = new ArrayNormalizer($this);
                $this->normalizersContainer->addNormalizer('array', $arrayNormalizer(...));
                return $arrayNormalizer($data);
            }
        } else {
            $normalizer = $this->normalizersContainer->get(get_debug_type($data));
            return $normalizer($data);
        }
    }

    public function encode(object|float|int|bool|array|string|null $data, string $format): string
    {
        $encoder = $this->encodersContainer->get($format);
        return $encoder($data);
    }
}
