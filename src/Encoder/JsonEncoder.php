<?php

namespace AndrewGos\Serializer\Encoder;

use AndrewGos\Serializer\Encoder\Exception\EncodingFailedException;

final readonly class JsonEncoder
{
    public function __construct(
        private int $flags = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE,
        private int $depth = 512,
    ) {
    }

    public function __invoke(array|string|float|int|bool|null $value): string
    {
        $result = json_encode($value, $this->flags, $this->depth);
        if ($result === false) {
            throw EncodingFailedException::new(get_debug_type($value), $this(...), json_last_error_msg());
        }
        return $result;
    }
}
