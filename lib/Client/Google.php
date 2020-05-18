<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

use EzSystems\EzPlatformAutomatedTranslation\Exception\ClientNotConfiguredException;
use EzSystems\EzPlatformAutomatedTranslation\Exception\InvalidLanguageCodeException;
use GuzzleHttp\Client;

/**
 * Class GoogleTranslate:.
 */
class Google implements ClientInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * {@inheritdoc}
     */
    public function getServiceAlias(): string
    {
        return 'google';
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceFullName(): string
    {
        return 'Google Translate';
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration): void
    {
        if (!isset($configuration['apiKey'])) {
            throw new ClientNotConfiguredException('authKey is required');
        }
        $this->apiKey = $configuration['apiKey'];
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $payload, ?string $from, string $to): string
    {
        $parameters = [
            'key' => $this->apiKey,
            'target' => $this->normalized($to),
            'format' => 'html',
            'q' => $payload,
        ];

        if (null !== $from) {
            $parameters += [
                'source' => $this->normalized($from),
            ];
        }

        $http = new Client(
            [
                'base_uri' => 'https://translation.googleapis.com/',
                'timeout' => 10.0,
            ]
        );
        $response = $http->post('/language/translate/v2', ['form_params' => $parameters]);
        $json = json_decode($response->getBody()->getContents());

        return $json->data->translations[0]->translatedText;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsLanguage(string $languageCode)
    {
        return \in_array($this->normalized($languageCode), self::LANGUAGE_CODES);
    }

    /**
     * @param string $languageCode
     *
     * @return string
     */
    private function normalized(string $languageCode): string
    {
        if (\in_array($languageCode, self::LANGUAGE_CODES)) {
            return $languageCode;
        }
        $twoLetters = substr($languageCode, 0, 2);
        if (\in_array($twoLetters, self::LANGUAGE_CODES)) {
            return $twoLetters;
        }
        if ('zh_CN' === $languageCode || 'zh_HK' === $languageCode) {
            return 'zh-CN';
        }
        if ('zh_TW' === $languageCode) {
            return 'zh-TW';
        }
        throw new InvalidLanguageCodeException($languageCode, $this->getServiceAlias());
    }

    /**
     * Google List of available code https://cloud.google.com/translate/docs/languages.
     */
    private const LANGUAGE_CODES = [
        'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca', 'co', 'hr', 'ur', 'uz', 'ta', 'tg',
        'cs', 'da', 'nl', 'en', 'eo', 'et', 'fi', 'fr', 'fy', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'ha', 'iw', 'sv',
        'hi', 'hu', 'gd', 'sr', 'st', 'ro', 'ru', 'sm', 'pa', 'te', 'th', 'tr', 'uk', 'yi', 'yo', 'zu', 'xh', 'sw',
        'is', 'ig', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk', 'km', 'ko', 'ku', 'ky', 'lo', 'la', 'lv', 'lt', 'lb',
        'cy', 'mk', 'mg', 'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'vi', 'sn', 'sd', 'si', 'sk', 'pl', 'pt',
        'fa', 'no', 'ny', 'ps', 'sl', 'so', 'es', 'su', 'tl', 'ceb', 'zh-CN', 'zh-TW', 'hmn', 'haw', 'he',
    ];
}
