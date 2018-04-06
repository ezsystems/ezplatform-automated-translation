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

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

use EzSystems\EzPlatformAutomatedTranslation\Exception\ClientNotConfiguredException;
use EzSystems\EzPlatformAutomatedTranslation\Exception\InvalidLanguageCodeException;
use GuzzleHttp\Client;

/**
 * Class Deepl.
 */
class Deepl implements ClientInterface
{
    /**
     * @var string
     */
    private $authKey;

    /**
     * {@inheritdoc}
     */
    public function getServiceAlias(): string
    {
        return 'deepl';
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceFullName(): string
    {
        return 'Deepl';
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration): void
    {
        if (!isset($configuration['authKey'])) {
            throw new ClientNotConfiguredException('authKey is required');
        }
        $this->authKey = $configuration['authKey'];
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $payload, ?string $from, string $to): string
    {
        $parameters = [
            'auth_key'     => $this->authKey,
            'target_lang'  => $this->normalized($to),
            'tag_handling' => 'xml',
            'text'         => $payload,
        ];

        if (null !== $from) {
            $parameters += [
                'source_lang' => $this->normalized($from),
            ];
        }

        $http     = new Client(
            [
                'base_uri' => 'https://api.deepl.com',
                'timeout'  => 5.0,
            ]
        );
        $response = $http->post('/v1/translate', ['form_params' => $parameters]);
        // May use the native json method from guzzle
        $json = json_decode($response->getBody()->getContents());

        return $json->translations[0]->text;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsLanguage(string $languageCode)
    {
        return \in_array($this->normalized($languageCode), self::LANGUAGE_CODES);
    }

    /**
     * {@inheritdoc}
     */
    private function normalized(string $languageCode): string
    {
        if (\in_array($languageCode, self::LANGUAGE_CODES)) {
            return $languageCode;
        }

        $code = strtoupper(substr($languageCode, 0, 2));
        if (\in_array($code, self::LANGUAGE_CODES)) {
            return $code;
        }

        throw new InvalidLanguageCodeException($languageCode, $this->getServiceAlias());
    }

    /**
     * List of available code https://www.deepl.com/api.html.
     */
    private const LANGUAGE_CODES = ['EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL'];
}
