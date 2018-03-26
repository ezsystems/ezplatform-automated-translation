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

use eZ\Publish\API\Repository\Values\Content\Content;

/**
 * Class TranslatorGuard.
 *
 * This class is in charge to check that the Translator can do whatever it wants to do
 *
 * - check that the source Language Code exists on the Source Content
 * - check that the target Language Code exists in the Content Repository
 * - provides enforce* methods that work like a "assertion methods" and triggers Exceptions
 *
 * Also it is in charge to get the Content in the source Language Code
 */
class TranslatorGuard
{
    use RepositoryAware;

    /**
     * @param Content $content
     * @param string  $languageCode
     *
     * @return bool
     */
    public function isLanguageVersionExist(Content $content, string $languageCode): bool
    {
        return \in_array($languageCode, $content->versionInfo->languageCodes);
    }

    /**
     * @param string $languageCode
     *
     * @return bool
     */
    public function isTargetLanguageVersionExist(string $languageCode): bool
    {
        $languages = $this->repository->getContentLanguageService()->loadLanguages();
        foreach ($languages as $language) {
            if ($language->enabled && $language->languageCode == $languageCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Content $content
     * @param string  $languageCode
     */
    public function enforceSourceLanguageVersionExist(Content $content, string $languageCode): void
    {
        if (!$this->isLanguageVersionExist($content, $languageCode)) {
            throw new \RuntimeException("Content {$content->id} does not have a Translation {$languageCode}.");
        }
    }

    /**
     * @param string $languageCode
     */
    public function enforceTargetLanguageExist(string $languageCode): void
    {
        if (!$this->isTargetLanguageVersionExist($languageCode)) {
            throw new \RuntimeException(
                "Language {$languageCode} does not exist (or is not enabled) in your Repository."
            );
        }
    }

    /**
     * @param Content $content
     * @param string  $languageCode
     *
     * @return Content
     */
    public function fetchContent(Content $content, ?string $languageCode): Content
    {
        if (null === $languageCode) {
            return $this->repository->getContentService()->loadContentByContentInfo($content->contentInfo);
        }

        return $this->repository->getContentService()->loadContentByContentInfo($content->contentInfo, [$languageCode]);
    }
}
