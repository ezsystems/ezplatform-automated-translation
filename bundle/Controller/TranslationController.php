<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\TranslationController as BaseTranslationController;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TranslationController extends Controller
{
    /** @var \EzSystems\EzPlatformAdminUiBundle\Controller\TranslationController */
    private $translationController;

    public function __construct(BaseTranslationController $translationController)
    {
        $this->translationController = $translationController;
    }

    public function addAction(Request $request): Response
    {
        $response = $this->translationController->addAction($request);

        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();
        $contentTranslatePattern = str_replace(
            '/',
            '\/?',
            urldecode(
                $this->generateUrl(
                    'ezplatform.content.translate',
                    [
                        'contentId' => '([0-9]*)',
                        'fromLanguageCode' => '([a-zA-Z-]*)',
                        'toLanguageCode' => '([a-zA-Z-]*)',
                    ]
                )
            )
        );

        // admin-ui v3.3.6 introduces different route `ibexa.content.translate_with_location.proxy`
        // when translated content is created.
        $contentTranslateWithLocationPattern = str_replace(
            '/',
            '\/?',
            urldecode(
                $this->generateUrl(
                    'ibexa.content.translate_with_location.proxy',
                    [
                        'contentId' => '([0-9]*)',
                        'fromLanguageCode' => '([a-zA-Z-]*)',
                        'toLanguageCode' => '([a-zA-Z-]*)',
                        'locationId' => '([0-9]*)',
                    ]
                )
            )
        );

        $serviceAlias = $request->request->get('add-translation')['translatorAlias'] ?? '';

        if ('' === $serviceAlias || (
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslatePattern) &&
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslateWithLocationPattern)
        )) {
            return $response;
        }

        $response->setTargetUrl(sprintf('%s?translatorAlias=%s', $targetUrl, $serviceAlias));

        return $response;
    }

    public function removeAction(Request $request): Response
    {
        return $this->translationController->removeAction($request);
    }

    private function targetUrlContainsPattern(string $targetUrl, string $pattern): bool
    {
        return 1 === preg_match("#{$pattern}#", $targetUrl);
    }
}
