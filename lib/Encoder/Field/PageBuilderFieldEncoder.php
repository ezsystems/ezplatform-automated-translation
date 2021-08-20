<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\Value as APIValue;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Value;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory;
use InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

final class PageBuilderFieldEncoder implements FieldEncoderInterface
{
    private const CDATA_FAKER_TAG = 'fake_blocks_cdata';

    private const XML_MARKUP = '<?xml version="1.0" encoding="UTF-8"?>';

    /** @var \EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderManager */
    private $blockAttributeEncoderManager;

    /** @var \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory */
    private $blockDefinitionFactory;

    public function __construct(
        BlockAttributeEncoderManager $blockAttributeEncoderManager,
        BlockDefinitionFactory $blockDefinitionFactory
    ) {
        $this->blockAttributeEncoderManager = $blockAttributeEncoderManager;
        $this->blockDefinitionFactory = $blockDefinitionFactory;
    }

    public function canEncode(Field $field): bool
    {
        return class_exists(Value::class) && $field->value instanceof Value;
    }

    public function canDecode(string $type): bool
    {
        return class_exists(Value::class) && Value::class === $type;
    }

    public function encode(Field $field): string
    {
        /** @var Value $value */
        $value = $field->value;
        $page = $value->getPage();
        $blocks = [];

        foreach ($page->getBlockIterator() as $block) {
            $blockDefinition = $this->blockDefinitionFactory->getBlockDefinition($block->getType());
            $attrs = [];

            foreach ($block->getAttributes() as $attribute) {
                $attributeType = $blockDefinition->getAttributes()[$attribute->getName()]->getType();

                if (null === ($attributeValue = $this->encodeBlockAttribute($attributeType, $attribute->getValue()))) {
                    continue;
                }

                $attrs[$attribute->getName()] = [
                    '@type' => $attributeType,
                    '#' => $attributeValue,
                ];
            }

            $blocks[$block->getId()] = [
              'name' => $block->getName(),
              'attributes' => $attrs,
            ];
        }

        $encoder = new XmlEncoder();
        $payload = $encoder->encode($blocks, XmlEncoder::FORMAT, [
            XmlEncoder::ROOT_NODE_NAME => 'blocks',
        ]);

        $payload = str_replace('<?xml version="1.0"?>' . "\n", '', $payload);

        $payload = str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );

        return (string) $payload;
    }

    public function decode(string $value, $previousFieldValue): APIValue
    {
        $encoder = new XmlEncoder();
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[' . self::XML_MARKUP, ']]>'],
            $value
        );

        /** @var Value $previousFieldValue */
        $page = clone $previousFieldValue->getPage();
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);

        foreach ($decodeArray as $blockId => $xmlValue) {
            $block = $page->getBlockById((string) $blockId);
            $block->setName($xmlValue['name']);

            if (is_array($xmlValue['attributes'])) {
                foreach ($xmlValue['attributes'] as $attributeName => $attribute) {
                    if (null === ($attributeValue = $this->decodeBlockAttribute($attribute['@type'], $attribute['#']))) {
                        continue;
                    }

                    $block->getAttribute($attributeName)->setValue($attributeValue);
                }
            }
        }

        return new Value($page);
    }

    /**
     * @param mixed $value
     */
    private function encodeBlockAttribute(string $type, $value): ?string
    {
        try {
            $value = $this->blockAttributeEncoderManager->encode($type, $value);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        return $value;
    }

    private function decodeBlockAttribute(string $type, string $value): ?string
    {
        try {
            $value = $this->blockAttributeEncoderManager->decode($type, $value);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        return $value;
    }
}
