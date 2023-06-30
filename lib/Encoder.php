<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\Value;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\FieldEncoderManager;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException;
use EzSystems\EzPlatformAutomatedTranslationBundle\Event\FieldDecodeEvent;
use EzSystems\EzPlatformAutomatedTranslationBundle\Event\FieldEncodeEvent;
use EzSystems\EzPlatformAutomatedTranslationBundle\Events;
use InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class Encoder.
 *
 * Google Translate and Deepl (and probably others) are able to "ignore" markups when they translate
 *
 * This Encoder basically encodes a Field[] in XML of Translatable Fields (TextLineValue,TextBlockValue and
 * RichTextValue)
 *
 * Ex:
 *      <response>
 *             <title>The string value</title>
 *             <description>>The string value</description>
 *             <fieldIdentifier>value of the field converted into string</fieldIdentifier>
 *      </response>
 *
 * But as a RichTextValue is already a XML, the content Repository returns a valid XML already
 *         <?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>
 *
 * Then you end up with an XML Encoded like this (look for the <![CDATA[)
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <![CDATA[<?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>]]>
 *              </description>
 *      </response>
 *
 * Which is bad because remote translation services are going to try to translate inside <![CDATA[ ]]>
 *
 * Then this Encoder fixes that, trusting the fact that RichTextValue is a valid XML
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <fakecdata><section><para>lorem ipsum</para></section></fakecdata>
 *              </description>
 *      </response>
 *
 * Wrapping the valid XML in "<fakecdata>", the global XML is still valid, and the translation works
 *
 * The decode function reverses the wrapping.
 */
class Encoder
{
    /**
     * Use to fake the <![CDATA[ something ]]> to <fakecdata> something </fakecdata>.
     */
    private const CDATA_FAKER_TAG = 'fakecdata';

    private const XML_MARKUP = '<?xml version="1.0" encoding="UTF-8"?>';

    /** @var ContentTypeService */
    private $contentTypeService;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\FieldEncoderManager */
    private $fieldEncoderManager;

    public function __construct(
        ContentTypeService $contentTypeService,
        EventDispatcherInterface $eventDispatcher,
        FieldEncoderManager $fieldEncoderManager
    ) {
        $this->contentTypeService = $contentTypeService;
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldEncoderManager = $fieldEncoderManager;
    }

    public function encode(Content $content): string
    {
        $results = [];
        $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
        foreach ($content->getFields() as $field) {
            $identifier = $field->fieldDefIdentifier;
            $fieldDefinition = $contentType->getFieldDefinition($identifier);
            if (!$fieldDefinition->isTranslatable) {
                continue;
            }
            $type = \get_class($field->value);

            if (null === ($value = $this->encodeField($field))) {
                continue;
            }

            $results[$identifier] = [
                '#' => $value,
                '@type' => $type,
            ];
        }

        $encoder = new XmlEncoder();
        $payload = $encoder->encode($results, XmlEncoder::FORMAT);
        // here Encoder has  decorated with CDATA, we don't want the CDATA
        return str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );
    }

    public function decode(string $xml, Content $sourceContent): array
    {
        $encoder = new XmlEncoder();
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[' . self::XML_MARKUP, ']]>'],
            $xml
        );

        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);
        $results = [];
        foreach ($decodeArray as $fieldIdentifier => $xmlValue) {
            $previousFieldValue = $sourceContent->getField($fieldIdentifier)->value;
            $type = $xmlValue['@type'];
            $stringValue = $xmlValue['#'];

            if (null === ($fieldValue = $this->decodeField($type, $stringValue, $previousFieldValue))) {
                continue;
            }

            if (!in_array(SPIValue::class, class_implements($type))) {
                throw new InvalidArgumentException(sprintf(
                    'Unable to instantiate class %s, it should implement %s', $type, SPIValue::class
                ));
            }

            if (!is_a($type, get_class($fieldValue))) {
                throw new InvalidArgumentException(sprintf(
                    'Decoded field class mismatch: expected %s, actual: %s', $type, get_class($fieldValue)
                ));
            }

            $results[$fieldIdentifier] = $fieldValue;
        }

        return $results;
    }

    private function encodeField(Field $field): ?string
    {
        try {
            $value = $this->fieldEncoderManager->encode($field);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        $event = new FieldEncodeEvent($field, $value);
        $this->eventDispatcher->dispatch($event, Events::POST_FIELD_ENCODE);

        return $event->getValue();
    }

    /**
     * @param mixed $previousFieldValue
     */
    private function decodeField(string $type, string $value, $previousFieldValue): ?Value
    {
        try {
            $fieldValue = $this->fieldEncoderManager->decode($type, $value, $previousFieldValue);
        } catch (InvalidArgumentException | EmptyTranslatedFieldException $e) {
            return null;
        }

        $event = new FieldDecodeEvent($type, $fieldValue, $previousFieldValue);
        $this->eventDispatcher->dispatch($event, Events::POST_FIELD_DECODE);

        return $event->getValue();
    }
}
