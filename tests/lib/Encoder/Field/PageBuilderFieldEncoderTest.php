<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\AutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\Repository\ProxyFactory\ProxyGenerator;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\PageBuilderFieldEncoder;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Attribute;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\BlockValue;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Page;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Zone;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Value;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockAttributeDefinition;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\LazyLoadingInterface;

class PageBuilderFieldEncoderTest extends TestCase
{
    public const ATTRIBUTE_VALUE = 'ibexa';

    public function testEncode(): void
    {
        $blockAttributeEncoderManagerMock = $this->getMockBuilder(BlockAttributeEncoderManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $blockDefinitionFactoryMock = $this->getMockBuilder(BlockDefinitionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $blockDefinitionFactoryMock
            ->expects(self::atLeastOnce())
            ->method('getBlockDefinition')
            ->withAnyParameters()
            ->willReturn($this->getBlockDefinition());

        $blockAttributeEncoderManagerMock
            ->expects(self::atLeastOnce())
            ->method('encode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder($blockAttributeEncoderManagerMock, $blockDefinitionFactoryMock);

        self::assertTrue($subject->canEncode($field));

        $result = $subject->encode($field);

        self::assertEquals($this->getEncodeResult(), trim($result));
    }

    public function testDecode(): void
    {
        $blockAttributeEncoderManagerMock = $this->getMockBuilder(BlockAttributeEncoderManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $blockDefinitionFactoryMock = $this->getMockBuilder(BlockDefinitionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $blockAttributeEncoderManagerMock
            ->expects(self::atLeastOnce())
            ->method('decode')
            ->withAnyParameters()
            ->willReturn(self::ATTRIBUTE_VALUE);

        $field = $this->getLandingPageField();
        $subject = new PageBuilderFieldEncoder($blockAttributeEncoderManagerMock, $blockDefinitionFactoryMock);

        self::assertTrue($subject->canDecode(get_class($field->value)));

        $result = $subject->decode(
            $this->getEncodeResult(),
            $field->value
        );

        self::assertInstanceOf(Value::class, $result);
        self::assertEquals(new Value($this->getPage()), $result);
    }

    private function getLandingPageField(): Field
    {
        $proxyManager = new ProxyGenerator('var/cache/repository/proxy');
        $initializer = function (
            &$value,
            LazyLoadingInterface $proxy,
            $method,
            array $parameters,
            &$initializer
        ): bool {
            $initializer = null;
            $value = new Value($this->getPage());
        
            return true;
        };

        $valueProxy = $proxyManager->createProxy(Value::class, $initializer);
        
        return new Field([
            'fieldDefIdentifier' => 'field_landing_page',
            'value' => $valueProxy,
        ]);
    }

    private function getPage(): Page
    {
        return new Page('default', [$this->createZone()]);
    }

    private function createZone(): Zone
    {
        return new Zone('1', 'Foo', [
            new BlockValue(
                '1',
                'tag',
                'Code',
                'default',
                null,
                null,
                '',
                null,
                null,
                [
                    new Attribute(
                        '1',
                        'content',
                        self::ATTRIBUTE_VALUE
                    ),
                ]
            ),
        ]);
    }

    private function getBlockDefinition(): BlockDefinition
    {
        $blockDefinition = new BlockDefinition();
        $blockDefinition->setIdentifier('tag');
        $blockDefinition->setName('Code');
        $blockDefinition->setCategory('default');
        $blockDefinition->setThumbnail('fake_thumbnail');
        $blockDefinition->setVisible(true);
        $blockDefinition->setConfigurationTemplate('fake_configuration_template');
        $blockDefinition->setViews([]);

        $attributeDefinitions = [];
        $blockAttributeDefinition = new BlockAttributeDefinition();
        $blockAttributeDefinition->setIdentifier('1');
        $blockAttributeDefinition->setName('content');
        $blockAttributeDefinition->setType('string');
        $blockAttributeDefinition->setConstraints([]);
        $blockAttributeDefinition->setValue(self::ATTRIBUTE_VALUE);
        $blockAttributeDefinition->setCategory('default');
        $blockAttributeDefinition->setOptions([]);

        $attributeDefinitions['content'] = $blockAttributeDefinition;

        $blockDefinition->setAttributes($attributeDefinitions);

        return $blockDefinition;
    }

    private function getEncodeResult(): string
    {
        return '<blocks><item key="1"><name>Code</name><attributes><content type="string">' .
            self::ATTRIBUTE_VALUE . '</content></attributes></item></blocks>';
    }
}
