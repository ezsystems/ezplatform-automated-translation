<?php
/**
 * eZ Automated Translation Bundle.
 *
 * @package   EzSystems\eZAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\Core\FieldType\RichText\Value as RichTextValue;
use eZ\Publish\Core\FieldType\TextLine\Value as TextLineValue;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

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

    /**
     * Allow to replace characters preserve eZ RichText Content.
     *
     * @var array
     */
    private $nonTranslatableCharactersHashMap;

    /**
     * Everything inside these tags must be preserve from translation.
     *
     * @var array
     */
    private $nonTranslatableTags;

    /**
     * Every attributes inside these tags must be preserve from translation.
     *
     * @var array
     */
    private $nonValidAttributeTags;

    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var array
     */
    private $placeHolderMap;

    /**
     * Encoder constructor.
     *
     * @param ContentTypeService $contentTypeService
     * @param ConfigResolver     $configResolver
     */
    public function __construct(ContentTypeService $contentTypeService, ConfigResolver $configResolver)
    {
        $this->contentTypeService = $contentTypeService;
        $this->placeHolderMap     = [];
        $tags                     = $configResolver->getParameter(
            'nontranslatabletags',
            'ez_platform_automated_translation'
        );
        $chars                    = $configResolver->getParameter(
            'nontranslatablecharacters',
            'ez_platform_automated_translation'
        );

        $attributes = $configResolver->getParameter(
            'nonnalidattributetags',
            'ez_platform_automated_translation'
        );

        $this->nonTranslatableTags              = ['ezembed'] + $tags;
        $this->nonTranslatableCharactersHashMap = ["\n" => 'XXXEOLXXX'] + $chars;
        $this->nonValidAttributeTags            = ['title'] + $attributes;
    }

    /**
     * @param Content $content
     *
     * @return string
     */
    public function encode(Content $content): string
    {
        $results     = [];
        $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
        foreach ($content->getFields() as $field) {
            $identifier      = $field->fieldDefIdentifier;
            $fieldDefinition = $contentType->getFieldDefinition($identifier);
            if (!$fieldDefinition->isTranslatable) {
                continue;
            }
            $type = \get_class($field->value);
            // Note that TextBlock is a TextLine
            if ($field->value instanceof TextLineValue) {
                $value                = (string) $field->value;
                $results[$identifier] = ['#' => $value, '@type' => $type];
                continue;
            }
            if ($field->value instanceof RichTextValue) {
                // we need to remove that to make it a good XML
                $value                = $this->richTextEncode($field->value);
                $results[$identifier] = ['#' => $value, '@type' => $type];
            }
            if ($field->value instanceof \EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value) {
                // we need to remove that to make it a good XML
                //
                $value                = $this->richTextEncode($field->value);
                $results[$identifier] = ['#' => $value, '@type' => $type];
            }
        }

        $encoder = new XmlEncoder();
        $payload = $encoder->encode($results, XmlEncoder::FORMAT);
        // here Encoder has  decorated with CDATA, we don't want the CDATA
        $payload = str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );

        return $payload;
    }

    /**
     * @param string $xml
     *
     * @return array
     */
    public function decode(string $xml): array
    {
        $encoder     = new XmlEncoder();
        $data        = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[' . self::XML_MARKUP, ']]>'],
            $xml
        );
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);
        $results     = [];
        foreach ($decodeArray as $fieldIdentifier => $xmlValue) {
            $type  = $xmlValue['@type'];
            $value = $xmlValue['#'];
            if (RichTextValue::class === $type) {
                $value = $this->richTextDecode($value);
            }
            if (\EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value::class === $type) {
                $value = $this->richTextDecode($value);
            }
            $trimmedValue = trim($value);
            if ('' === $trimmedValue) {
                continue;
            }
            $results[$fieldIdentifier] = new $type($trimmedValue);
        }

        return $results;
    }

    /**
     * @param RichTextValue $value
     *
     * @return string
     */
    public function richTextEncode(RichTextValue $value): string
    {
        $xmlString = (string) $value;
        $xmlString = substr($xmlString, strpos($xmlString, '>') + 1);
        $xmlString = str_replace(
            array_keys($this->nonTranslatableCharactersHashMap),
            array_values($this->nonTranslatableCharactersHashMap),
            $xmlString
        );

        foreach ($this->nonTranslatableTags as $tag) {
            $xmlString = preg_replace_callback(
                '#<' . $tag . '(.[^>]*)>(.*)</' . $tag . '>#uim',
                function ($matches) use ($tag) {
                    $hash                        = sha1($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<{$tag}>{$hash}</{$tag}>";
                },
                $xmlString
            );
        }
        foreach ($this->nonValidAttributeTags as $tag) {
            $xmlString = preg_replace_callback(
                '#<' . $tag . '(.[^>]*)>#uim',
                function ($matches) use ($tag) {
                    $hash                        = sha1($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<fake{$tag} {$hash}>";
                },
                $xmlString
            );
            $xmlString = str_replace("</{$tag}>", "</fake{$tag}>", $xmlString);
        }

        return $xmlString;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function richTextDecode(string $value): string
    {
        $value = str_replace(
            array_values($this->nonTranslatableCharactersHashMap),
            array_keys($this->nonTranslatableCharactersHashMap),
            $value
        );
        foreach ($this->nonTranslatableTags as $tag) {
            $value = preg_replace_callback(
                '#<' . $tag . '>(.*)</' . $tag . '>#uim',
                function ($matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
        }
        foreach ($this->nonValidAttributeTags as $tag) {
            $value = preg_replace_callback(
                '#<fake' . $tag . '(.[^>]*)>#uim',
                function ($matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
            $value = str_replace("</fake{$tag}>", "</{$tag}>", $value);
        }

        return $value;
    }
}
