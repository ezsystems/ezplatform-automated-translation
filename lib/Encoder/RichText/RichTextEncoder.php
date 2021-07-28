<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText;

use eZ\Publish\Core\MVC\ConfigResolverInterface;

final class RichTextEncoder
{
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

    /** @var array */
    private $placeHolderMap;

    public function __construct(
        ConfigResolverInterface $configResolver
    ) {
        $tags = $configResolver->getParameter(
            'nontranslatabletags',
            'ez_platform_automated_translation'
        );

        $chars = $configResolver->getParameter(
            'nontranslatablecharacters',
            'ez_platform_automated_translation'
        );

        $attributes = $configResolver->getParameter(
            'nonnalidattributetags',
            'ez_platform_automated_translation'
        );

        $this->nonTranslatableTags = ['ezvalue', 'ezconfig', 'ezembed'] + $tags;
        $this->nonTranslatableCharactersHashMap = ["\n" => 'XXXEOLXXX'] + $chars;
        $this->nonValidAttributeTags = ['title'] + $attributes;
        $this->placeHolderMap = [];
    }

    public function encode(string $xmlString): string
    {
        $xmlString = substr($xmlString, strpos($xmlString, '>') + 1);
        $xmlString = $this->encodeNonTranslatableCharacters($xmlString);
        foreach ($this->nonTranslatableTags as $tag) {
            $xmlString = preg_replace_callback(
                '#<' . $tag . '(.[^>]*)>(.*)</' . $tag . '>#Uuim',
                function ($matches) use ($tag) {
                    $hash = sha1($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<{$tag}>{$hash}</{$tag}>";
                },
                $xmlString
            );
        }
        foreach ($this->nonValidAttributeTags as $tag) {
            $xmlString = preg_replace_callback(
                '#<' . $tag . '(.[^>]*)>#Uuim',
                function ($matches) use ($tag) {
                    $hash = sha1($matches[0]);
                    $this->placeHolderMap[$hash] = $matches[0];

                    return "<fake{$tag} {$hash}>";
                },
                $xmlString
            );
            $xmlString = str_replace("</{$tag}>", "</fake{$tag}>", $xmlString);
        }

        return $xmlString;
    }

    public function decode(string $value): string
    {
        $value = $this->decodeNonTranslatableCharacters($value);
        foreach (array_reverse($this->nonTranslatableTags) as $tag) {
            $value = preg_replace_callback(
                '#<' . $tag . '>(.*)</' . $tag . '>#Uuim',
                function ($matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
        }
        foreach ($this->nonValidAttributeTags as $tag) {
            $value = preg_replace_callback(
                '#<fake' . $tag . '(.[^>]*)>#Uuim',
                function ($matches) {
                    return $this->placeHolderMap[trim($matches[1])];
                },
                $value
            );
            $value = str_replace("</fake{$tag}>", "</{$tag}>", $value);
        }

        return $this->decodeNonTranslatableCharacters($value);
    }

    private function encodeNonTranslatableCharacters(string $value): string
    {
        return str_replace(
            array_keys($this->nonTranslatableCharactersHashMap),
            array_values($this->nonTranslatableCharactersHashMap),
            $value
        );
    }

    private function decodeNonTranslatableCharacters(string $value): string
    {
        return str_replace(
            array_values($this->nonTranslatableCharactersHashMap),
            array_keys($this->nonTranslatableCharactersHashMap),
            $value
        );
    }
}
