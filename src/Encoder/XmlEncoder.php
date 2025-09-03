<?php

namespace AndrewGos\Serializer\Encoder;

final readonly class XmlEncoder
{
    public function __invoke(array|string|float|int|bool|null $data): string
    {
        $references = [];
        $referencesCode = [];
        $encodedValue = "<data>{$this->encodeValue($data, $references, $referencesCode)}</data>";
        array_walk(
            $referencesCode,
            static fn(string &$value, string $key) => $value = "<reference key=\"$key\">$value</reference>",
        );
        $referencesCode = implode('', $referencesCode);
        $referencesCode = "<references>$referencesCode</references>";
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
$referencesCode
$encodedValue
</root>
XML;
    }

    private function encodeValue(array|string|float|int|bool|null &$data, array &$references, array &$referencesCode): string
    {
        return match(get_debug_type($data)) {
            'null' => $this->encodeNull($data),
            'bool' => $this->encodeBool($data),
            'int' => $this->encodeInt($data),
            'float' => $this->encodeFloat($data),
            'string' => $this->encodeString($data),
            'array' => $this->encodeArray($data, $references, $referencesCode),
        };
    }

    private function encodeNull(null &$data): string
    {
        return '<null/>';
    }

    private function encodeBool(bool &$data): string
    {
        $encodedValue = (int)$data;
        return "<bool>$encodedValue</bool>";
    }

    private function encodeInt(int &$data): string
    {
        return "<int>$data</int>";
    }

    private function encodeFloat(float &$data): string
    {
        return "<float>$data</float>";
    }

    private function encodeString(string &$data): string
    {
        if (preg_match('/[<>&]/', $data)) {
            $encodedValue = str_replace(']]>', ']]]]><![CDATA[>', $data);
            return "<string><![CDATA[$encodedValue]]></string>";
        }

        $encodedValue = htmlspecialchars($data, ENT_XML1, 'UTF-8');
        return "<string>$encodedValue</string>";
    }

    private function encodeArray(array &$data, array &$references, array &$referencesCode): string
    {
        $refKey = uniqid();
        foreach ($references as $key => &$reference) {
            if ($this->isRefsEquals($data, $reference)) {
                $refKey = $key;
                break;
            }
        }
        if (!isset($references[$refKey])) {
            $references[$refKey] = &$data;
            $referenceCode = '<array>';
            foreach ($data as $key => &$value) {
                $key = htmlspecialchars((string)$key, ENT_XML1, 'UTF-8');
                $referenceCode .= "<item key=\"$key\">{$this->encodeValue($value, $references, $referencesCode)}</item>";
            }
            $referenceCode .= '</array>';
            $referencesCode[$refKey] = $referenceCode;
        }
        return "<reference key=\"$refKey\"/>";
    }

    private function isRefsEquals(mixed &$left, mixed &$right): bool
    {
        if ($left !== $right) {
            return false;
        }
        $lVal = $left;
        $left = !($lVal === true);
        $result = $left === $right;
        $left = $lVal;
        return $result;
    }
}
