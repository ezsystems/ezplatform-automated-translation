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

use eZ\Publish\API\Repository\Values\Content\Field;
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
     * @param Field[] $fields
     *
     * @return string
     */
    public function encode(array $fields): string
    {
        $results = [];
        foreach ($fields as $field) {
            $identifier = $field->fieldDefIdentifier;
            $type       = \get_class($field->value);
            // Note that TextBlock is a TextLine
            if ($field->value instanceof TextLineValue) {
                $value                = (string) $field->value;
                $results[$identifier] = ['#' => $value, '@type' => $type];
                continue;
            }
            if ($field->value instanceof RichTextValue) {
                // we need to remove that to make it a good XML
                $value                = substr((string) $field->value, \strlen(self::XML_MARKUP));
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
            $type         = $xmlValue['@type'];
            $value        = $xmlValue['#'];
            $trimmedValue = trim($value);
            if ('' === $trimmedValue) {
                continue;
            }
            $results[$fieldIdentifier] = new $type($trimmedValue);
        }

        return $results;
    }
}
