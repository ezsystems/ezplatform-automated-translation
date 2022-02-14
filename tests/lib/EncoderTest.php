<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\FieldType\TextLine;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText;

class EncoderTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->configResolver = $this
            ->getMockBuilder(ConfigResolverInterface::class)
            ->getMock();

        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with($this->equalTo('nontranslatabletags'), $this->equalTo('ez_platform_automated_translation'))
            ->willReturn([]);

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with($this->equalTo('nontranslatablecharacters'), $this->equalTo('ez_platform_automated_translation'))
            ->willReturn([]);

        $this->configResolver
            ->expects($this->at(2))
            ->method('getParameter')
            ->with($this->equalTo('nonnalidattributetags'), $this->equalTo('ez_platform_automated_translation'))
            ->willReturn([]);
    }

    public function testEncodeTwoTextline()
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $contentType = $this->getMockForAbstractClass(
            ContentType::class,
            [],
            '',
            true,
            true,
            true,
            ['getFieldDefinition']
        );
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([
                [
                    'fieldTypeIdentifier' => 'ezstring',
                    'isTranslatable' => true,
                ],
            ])
            ->getMockForAbstractClass();

        $contentType
            ->expects($this->at(0))
            ->method('getFieldDefinition')
            ->with('field_1_textline')
            ->will($this->returnValue($fieldDefinition));

        $contentType
            ->expects($this->at(1))
            ->method('getFieldDefinition')
            ->with('field_2_textline')
            ->will($this->returnValue($fieldDefinition));

        $contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentType')
            ->with(123)
            ->will($this->returnValue($contentType));

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'field_1_textline',
                    'value' => new TextLine\Value('Some text 1'),
                ]),
                new Field([
                    'fieldDefIdentifier' => 'field_2_textline',
                    'value' => new TextLine\Value('Some text 2'),
                ]),
            ],
        ]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $this->configResolver
        );

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = $this->getFixture('testEncodeTwoTextline_expectedEncodeResult.xml');

        $this->assertEquals($expectedEncodeResult, $encodeResult);

        $fieldValues = array_reduce($content->getFields(), function ($collection, $field) {
            $collection[$field->fieldDefIdentifier] = $field->value;

            return $collection;
        }, []);

        $decodeResult = $subject->decode($encodeResult);

        $this->assertEquals($fieldValues, $decodeResult);
    }

    public function testEncodeTwoRichText()
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $contentType = $this->getMockForAbstractClass(
            ContentType::class,
            [],
            '',
            true,
            true,
            true,
            ['getFieldDefinition']
        );
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([
                [
                    'fieldTypeIdentifier' => 'ezrichtext',
                    'isTranslatable' => true,
                ],
            ])
            ->getMockForAbstractClass();

        $contentType
            ->expects($this->at(0))
            ->method('getFieldDefinition')
            ->with('field_1_richtext')
            ->will($this->returnValue($fieldDefinition));

        $contentType
            ->expects($this->at(1))
            ->method('getFieldDefinition')
            ->with('field_2_richtext')
            ->will($this->returnValue($fieldDefinition));

        $contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentType')
            ->with(123)
            ->will($this->returnValue($contentType));

        $xml1 = $this->getFixture('testEncodeTwoRichText_field1_richtext.xml');
        $xml2 = $this->getFixture('testEncodeTwoRichText_field2_richtext.xml');

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'field_1_richtext',
                    'value' => new RichText\Value($xml1),
                ]),
                new Field([
                    'fieldDefIdentifier' => 'field_2_richtext',
                    'value' => new RichText\Value($xml2),
                ]),
            ],
        ]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $this->configResolver
        );

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = $this->getFixture('testEncodeTwoRichText_expectedEncodeResult.xml');

        $this->assertEquals($expectedEncodeResult, $encodeResult);

        $fieldValues = array_reduce($content->getFields(), function ($collection, $field) {
            $collection[$field->fieldDefIdentifier] = $field->value;

            return $collection;
        }, []);

        $decodeResult = $subject->decode($encodeResult);

        $this->assertEquals($fieldValues, $decodeResult);
    }

    public function testEncodeTwoRichTextWithTwoEzembed()
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $contentType = $this->getMockForAbstractClass(
            ContentType::class,
            [],
            '',
            true,
            true,
            true,
            ['getFieldDefinition']
        );
        $fieldDefinition = $this->getMockBuilder(FieldDefinition::class)
            ->setConstructorArgs([
                [
                    'fieldTypeIdentifier' => 'ezrichtext',
                    'isTranslatable' => true,
                ],
            ])
            ->getMockForAbstractClass();

        $contentType
            ->expects($this->at(0))
            ->method('getFieldDefinition')
            ->with('field_1_richtext')
            ->will($this->returnValue($fieldDefinition));

        $contentType
            ->expects($this->at(1))
            ->method('getFieldDefinition')
            ->with('field_2_richtext')
            ->will($this->returnValue($fieldDefinition));

        $contentTypeServiceMock
            ->expects($this->once())
            ->method('loadContentType')
            ->with(123)
            ->will($this->returnValue($contentType));

        $xml1 = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field1_richtext.xml');
        $xml2 = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_field2_richtext.xml');

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [
                new Field([
                    'fieldDefIdentifier' => 'field_1_richtext',
                    'value' => new RichText\Value($xml1),
                ]),
                new Field([
                    'fieldDefIdentifier' => 'field_2_richtext',
                    'value' => new RichText\Value($xml2),
                ]),
            ],
        ]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $this->configResolver
        );

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = $this->getFixture('testEncodeTwoRichTextWithTwoEzembed_expectedEncodeResult.xml');

        $this->assertEquals($expectedEncodeResult, $encodeResult);

        $fieldValues = array_reduce($content->getFields(), function ($collection, $field) {
            $collection[$field->fieldDefIdentifier] = $field->value;

            return $collection;
        }, []);

        $decodeResult = $subject->decode($encodeResult);

        $this->assertEquals($fieldValues, $decodeResult);
    }

    /**
     * Returns ContentTypeService mock object.
     *
     * @return \eZ\Publish\API\Repository\ContentTypeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentTypeServiceMock()
    {
        $contentTypeServiceMock = $this
            ->getMockBuilder('eZ\Publish\API\Repository\ContentTypeService')
            ->getMock();

        return $contentTypeServiceMock;
    }

    protected function getFixture($name)
    {
        return file_get_contents(__DIR__ . '/../fixtures/' . $name);
    }
}
