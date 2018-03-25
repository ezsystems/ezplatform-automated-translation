<?php
/**
 * eZ Automated Translation Bundle.
 *
 * @package   EzSystems\eZAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use EzSystems\EzPlatformAdminUi\RepositoryForms\Data\ContentTranslationData;
use EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface;
use EzSystems\EzPlatformAutomatedTranslation\Client\Google;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use EzSystems\RepositoryForms\Data\Content\FieldData;

/**
 * Class Translator
 */
class Translator
{
    use RepositoryAware;

    /**
     * @var TranslatorGuard
     */
    private $guard;

    /**
     * @var LocaleConverterInterface
     */
    private $localeConverter;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * Translator constructor.
     *
     * @param TranslatorGuard          $guard
     * @param LocaleConverterInterface $localeConverter
     * @param ClientProvider           $clientProvider
     */
    public function __construct(
        TranslatorGuard $guard,
        LocaleConverterInterface $localeConverter,
        ClientProvider $clientProvider
    ) {
        $this->guard           = $guard;
        $this->localeConverter = $localeConverter;
        $this->clientProvider  = $clientProvider;
    }

    /**
     * @param string  $from
     * @param string  $to
     * @param string  $remoteServiceKey
     * @param Content $content
     *
     * @return Content
     */
    public function translateContent(string $from, string $to, string $remoteServiceKey, Content $content): Content
    {
        $this->guard->enforceSourceLanguageVersionExist($content, $from);
        $this->guard->enforceTargetLanguageExist($to);
        $sourceContent     = $this->guard->fetchContent($content, $from);
        $encoder           = new Encoder();
        $payload           = $encoder->encode($sourceContent->getFields());
        $posixFrom         = $this->localeConverter->convertToPOSIX($from);
        $posixTo           = $this->localeConverter->convertToPOSIX($to);
        $remoteService     = $this->clientProvider->get($remoteServiceKey);
        $translatedPayload = $remoteService->translate($payload, $posixFrom, $posixTo);
        $translatedFields  = $encoder->decode($translatedPayload);

        return $this->createNewTranslationDraft($content, $to, $translatedFields);
    }

    /**
     * @param Content $sourceContent
     * @param string  $languageCode
     * @param array   $translatedFields
     *
     * @return Content
     */
    private function createNewTranslationDraft(
        Content $sourceContent,
        string $newLanguageCode,
        array $translatedFields
    ): Content {
        $contentService                           = $this->repository->getContentService();
        $contentDraft                             = $contentService->createContentDraft($sourceContent->contentInfo);
        $contentUpdateStruct                      = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $newLanguageCode;

        $contentType = $this->repository->getContentTypeService()->loadContentType(
            $sourceContent->contentInfo->contentTypeId
        );

        foreach ($contentType->getFieldDefinitions() as $field) {
            /** @var FieldDefinition $field */
            $fieldName = $field->identifier;
            $newValue  = isset($translatedFields[$fieldName]) ? trim(
                $translatedFields[$fieldName]
            ) : $sourceContent->getFieldValue($fieldName);
            $contentUpdateStruct->setField($fieldName, $newValue);
        }

        return $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
    }
}
