<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;

class Translator
{
    /** @var TranslatorGuard */
    private $guard;

    /** @var LocaleConverterInterface */
    private $localeConverter;

    /** @var ClientProvider */
    private $clientProvider;

    /** @var Encoder */
    private $encoder;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    public function __construct(
        TranslatorGuard $guard,
        LocaleConverterInterface $localeConverter,
        ClientProvider $clientProvider,
        Encoder $encoder,
        ContentService $contentService,
        ContentTypeService $contentTypeService
    ) {
        $this->guard = $guard;
        $this->localeConverter = $localeConverter;
        $this->clientProvider = $clientProvider;
        $this->encoder = $encoder;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
    }

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

        return $this->encoder->decode($translatedPayload, $sourceContent);
    }

    public function getTranslatedContent(string $from, string $to, string $remoteServiceKey, Content $content): Content
    {
        $translatedFields = $this->getTranslatedFields($from, $to, $remoteServiceKey, $content);

        $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $to;

        $contentType = $this->contentTypeService->loadContentType(
            $content->contentInfo->contentTypeId
        );

        foreach ($contentType->getFieldDefinitions() as $field) {
            /** @var FieldDefinition $field */
            $fieldName = $field->identifier;
            $newValue = $translatedFields[$fieldName] ?? $content->getFieldValue($fieldName);
            $contentUpdateStruct->setField($fieldName, $newValue);
        }

        return $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
    }
}
