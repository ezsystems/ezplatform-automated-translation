<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Tests\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\RichTextFieldEncoder;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText;
use PHPUnit\Framework\TestCase;

class RichTextFieldEncoderTest extends TestCase
{
    public function testEncode()
    {
        $richTextEncoderMock = $this->getMockBuilder(RichTextEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $richTextEncoderMock
            ->expects($this->atLeastOnce())
            ->method('encode')
            ->withAnyParameters()
            ->willReturn('Some text 1');

        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $field = new Field([
            'fieldDefIdentifier' => 'field_1_richtext',
            'value' => new RichText\Value($xml1),
        ]);

        $subject = new RichTextFieldEncoder($richTextEncoderMock);
        $result = $subject->encode($field);

        $this->assertEquals('Some text 1', $result);
    }

    public function testDecode()
    {
        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $richTextEncoderMock = $this->getMockBuilder(RichTextEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $richTextEncoderMock
            ->expects($this->atLeastOnce())
            ->method('decode')
            ->withAnyParameters()
            ->willReturn($xml1);

        $field = new Field([
            'fieldDefIdentifier' => 'field_1_richtext',
            'value' => new RichText\Value($xml1),
        ]);

        $subject = new RichTextFieldEncoder($richTextEncoderMock);
        $result = $subject->decode(
            $xml1,
            $field->value
        );

        $this->assertInstanceOf(RichText\Value::class, $result);
        $this->assertEquals(new RichText\Value($xml1), $result);
    }

    protected function getFixture($name)
    {
        return file_get_contents(__DIR__ . '/../../../fixtures/' . $name);
    }
}
