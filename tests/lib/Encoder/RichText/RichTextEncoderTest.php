<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Tests\Encoder\RichText;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder;
use PHPUnit\Framework\TestCase;

class RichTextEncoderTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    public function setUp()
    {
        parent::setUp();

        $this->configResolver = $this
            ->getMockBuilder(ConfigResolverInterface::class)
            ->getMock();

        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with(
                $this->equalTo('non_translatable_tags'),
                $this->equalTo('ez_platform_automated_translation')
            )
            ->willReturn([]);

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with(
                $this->equalTo('non_translatable_self_closed_tags'),
                $this->equalTo('ez_platform_automated_translation')
            )
            ->willReturn([]);

        $this->configResolver
            ->expects($this->at(2))
            ->method('getParameter')
            ->with(
                $this->equalTo('non_translatable_characters'),
                $this->equalTo('ez_platform_automated_translation')
            )
            ->willReturn([]);

        $this->configResolver
            ->expects($this->at(3))
            ->method('getParameter')
            ->with(
                $this->equalTo('non_valid_attribute_tags'),
                $this->equalTo('ez_platform_automated_translation')
            )
            ->willReturn([]);
    }

    public function testEncodeAndDecodeRichtext()
    {
        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeTwoRichText_field1_richtext_encoded.xml');

        $this->assertEquals($expected, $encodeResult . "\n");

        $decodeResult = $subject->decode($encodeResult);

        $this->assertEquals($xml1, $decodeResult);
    }

    public function testEncodeAndDecodeRichtextEmbeded()
    {
        $xml1 = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field2_richtext.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field2_richtext_encoded.xml');

        $this->assertEquals($expected, $encodeResult . "\n");

        $decodeResult = $subject->decode($encodeResult);

        $this->assertEquals($xml1, $decodeResult);
    }

    public function testEncodeAndDecodeRichtextExtended()
    {
        $xml1 = $this->getFixture('testEncodeRichText_input.xml');

        $subject = new RichTextEncoder($this->configResolver);

        $encodeResult = $subject->encode($xml1);

        $expected = $this->getFixture('testEncodeRichText_input_encoded.xml');

        $this->assertEquals(trim($expected), trim($encodeResult));

        $decodeResult = $subject->decode($encodeResult);

        $decodeResult = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $decodeResult;

        $this->assertEquals($xml1, $decodeResult);
    }

    protected function getFixture($name)
    {
        return file_get_contents(__DIR__ . '/../../../fixtures/' . $name);
    }
}
