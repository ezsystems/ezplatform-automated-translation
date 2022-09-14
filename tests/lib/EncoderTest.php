<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Tests;

use EzSystems\EzPlatformAutomatedTranslation\Encoder;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\FieldEncoderManager;
use PHPUnit\Framework\TestCase;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\FieldType\TextLine;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EncoderTest extends TestCase
{
    public function testEncodeWithoutFields()
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $fieldEncoderManagerMock = $this->getMockBuilder(FieldEncoderManager::class)->getMock();

        $content = new Content([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 1,
                    'contentTypeId' => 123,
                ]),
            ]),
            'internalFields' => [],
        ]);

        $subject = new Encoder(
            $contentTypeServiceMock,
            $eventDispatcherMock,
            $fieldEncoderManagerMock
        );

        $encodeResult = $subject->encode($content);

        $expected = <<<XML
<?xml version="1.0"?>
<response/>

XML;

        $this->assertEquals($expected, $encodeResult);
    }

    public function testEncodeTwoTextline()
    {
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $fieldEncoderManagerMock = $this->getMockBuilder(FieldEncoderManager::class)->getMock();

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

        $fieldEncoderManagerMock
            ->expects($this->exactly(2))
            ->method('encode')
            ->withAnyParameters()
            ->will($this->returnValue('encoded'));

        $subject = new Encoder(
            $contentTypeServiceMock,
            $eventDispatcherMock,
            $fieldEncoderManagerMock
        );

        $encodeResult = $subject->encode($content);

        $expectedEncodeResult = <<<XML
<?xml version="1.0"?>
<response><field_1_textline type="eZ\Publish\Core\FieldType\TextLine\Value">encoded</field_1_textline><field_2_textline type="eZ\Publish\Core\FieldType\TextLine\Value">encoded</field_2_textline></response>

XML;

        $this->assertEquals($expectedEncodeResult, $encodeResult);
    }

    /**
     * Returns ContentTypeService mock object.
     *
     * @return \eZ\Publish\API\Repository\ContentTypeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentTypeServiceMock()
    {
        return $this
            ->getMockBuilder('eZ\Publish\API\Repository\ContentTypeService')
            ->getMock();
    }

    protected function getFixture($name)
    {
        return file_get_contents(__DIR__ . '/../fixtures/' . $name);
    }
}
