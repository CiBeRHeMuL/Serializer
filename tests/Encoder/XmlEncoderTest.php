<?php

namespace AndrewGos\Serializer\Tests\Encoder;

use AndrewGos\Serializer\Encoder\XmlEncoder;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class XmlEncoderTest extends TestCase
{
    private XmlEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encoder = new XmlEncoder();
    }

    public function testSimpleArray(): void
    {
        $data = ['a' => 1, 'b' => 'test', 'c' => true, 'd' => null];
        $xml = ($this->encoder)($data);

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $this->assertSame('1', $xpath->query('/root/references/reference/array/item[@key="a"]/int')->item(0)->nodeValue);
        $this->assertSame('test', $xpath->query('/root/references/reference/array/item[@key="b"]/string')->item(0)->nodeValue);
        $this->assertSame('1', $xpath->query('/root/references/reference/array/item[@key="c"]/bool')->item(0)->nodeValue);
        $this->assertNotNull($xpath->query('/root/references/reference/array/item[@key="d"]/null')->item(0));
    }

    public function testSelfReferencingArray(): void
    {
        $data = ['a' => 1];
        $data['a'] = &$data;

        $xml = ($this->encoder)($data);

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $references = $xpath->query('/root/references/reference');
        $this->assertEquals(2, $references->length);

        $dataRef = $xpath->query('/root/data/reference');
        $this->assertEquals(1, $dataRef->length);

        $refKey = $dataRef->item(0)->attributes->getNamedItem('key')->nodeValue;
        $referenceKey = $references->item(1)->attributes->getNamedItem('key')->nodeValue;
        $this->assertSame($refKey, $referenceKey);

        $innerRef = $xpath->query('/root/references/reference/array/item/reference');
        $this->assertEquals(2, $innerRef->length);
        $this->assertSame(
            $references->item(0)->attributes->getNamedItem('key')->nodeValue,
            $innerRef->item(1)->attributes->getNamedItem('key')->nodeValue,
        );
    }

    public function testMutualRecursionArrays(): void
    {
        $arr1 = [];
        $arr2 = ['b' => &$arr1];
        $arr1['a'] = &$arr2;

        $xml = ($this->encoder)($arr1);

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $references = $xpath->query('/root/references/reference');
        $this->assertEquals(3, $references->length);

        $dataRef = $xpath->query('/root/data/reference');
        $this->assertEquals(1, $dataRef->length);

        $refKeyInData = $dataRef->item(0)->attributes->getNamedItem('key')->nodeValue;

        $ref1 = $xpath->query('/root/references/reference[@key="' . $refKeyInData . '"]')->item(0);
        $this->assertNotNull($ref1, 'Reference for data not found');

        $innerRefKey = $xpath->query('.//reference', $ref1)->item(0)->attributes->getNamedItem('key')->nodeValue;
        $this->assertNotSame($refKeyInData, $innerRefKey, 'Inner reference should point to another reference');

        $ref2 = $xpath->query('/root/references/reference[@key="' . $innerRefKey . '"]')->item(0);
        $this->assertNotNull($ref2, 'The second reference in recursion not found');

        $secondInnerRefKey = $xpath->query('.//reference', $ref2)
            ->item(0)
            ->attributes
            ->getNamedItem('key')
            ->nodeValue;

        $ref3 = $xpath->query('/root/references/reference[@key="' . $secondInnerRefKey . '"]')->item(0);

        $finalRefKey = $xpath->query('.//reference', $ref3)->item(0)->attributes->getNamedItem('key')->nodeValue;
        $this->assertSame(
            $ref2->attributes->getNamedItem('key')->nodeValue,
            $finalRefKey,
            'The recursion loop is not closed correctly',
        );
    }

    public function testEncoderHasNoSideEffects(): void
    {
        $boolVar = true;
        $stringVar = '<test>&</test>';
        $data = [
            'my_bool' => &$boolVar,
            'my_string' => &$stringVar,
        ];

        // Сохраняем исходные значения
        $originalBoolVar = $boolVar;
        $originalStringVar = $stringVar;

        // Вызываем кодировщик
        ($this->encoder)($data);

        // Проверяем, что исходные переменные не были изменены
        $this->assertSame($originalBoolVar, $boolVar, 'Boolean variable was modified');
        $this->assertIsBool($boolVar, 'Boolean variable changed its type');
        $this->assertSame($originalStringVar, $stringVar, 'String variable was modified');
    }
}
