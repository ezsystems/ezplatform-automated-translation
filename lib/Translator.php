<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;

/**
 * Class Translator.
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
     * @var Encoder
     */
    private $encoder;

    /**
     * Translator constructor.
     *
     * @param TranslatorGuard          $guard
     * @param LocaleConverterInterface $localeConverter
     * @param ClientProvider           $clientProvider
     * @param Encoder                  $encoder
     */
    public function __construct(
        TranslatorGuard $guard,
        LocaleConverterInterface $localeConverter,
        ClientProvider $clientProvider,
        Encoder $encoder
    ) {
        $this->guard = $guard;
        $this->localeConverter = $localeConverter;
        $this->clientProvider = $clientProvider;
        $this->encoder = $encoder;
    }

    /**
     * @param string  $from
     * @param string  $to
     * @param string  $remoteServiceKey
     * @param Content $content
     *
     * @return Content
     */
    public function getTranslatedFields(?string $from, ?string $to, string $remoteServiceKey, Content $content): array
    {
        $posixFrom = null;
        if (null !== $from) {
            $this->guard->enforceSourceLanguageVersionExist($content, $from);
            $posixFrom = $this->localeConverter->convertToPOSIX($from);
        }
        $this->guard->enforceTargetLanguageExist($to);

        $sourceContent = $this->guard->fetchContent($content, $from);
        $payload = $this->encoder->encode($sourceContent);
        $posixTo = $this->localeConverter->convertToPOSIX($to);
        $remoteService = $this->clientProvider->get($remoteServiceKey);
        $translatedPayload = $remoteService->translate($payload, $posixFrom, $posixTo);

        return $this->encoder->decode($translatedPayload);
    }

    /**
     * @param string  $from
     * @param string  $to
     * @param string  $remoteServiceKey
     * @param Content $content
     *
     * @return Content
     */
    public function getTranslatedContent(string $from, string $to, string $remoteServiceKey, Content $content): Content
    {
        $translatedFields = $this->getTranslatedFields($from, $to, $remoteServiceKey, $content);

        $contentService = $this->repository->getContentService();
        $contentDraft = $contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $to;

        $contentType = $this->repository->getContentTypeService()->loadContentType(
            $content->contentInfo->contentTypeId
        );

        foreach ($contentType->getFieldDefinitions() as $field) {
            /** @var FieldDefinition $field */
            $fieldName = $field->identifier;
            $newValue = $translatedFields[$fieldName] ?? $content->getFieldValue($fieldName);
            $contentUpdateStruct->setField($fieldName, $newValue);
        }

        return $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
    }
}
