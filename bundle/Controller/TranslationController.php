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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class TranslationController extends BaseTranslationController
{
//    /** @var \EzSystems\EzPlatformAdminUiBundle\Controller\TranslationController */
//    private $translationController;
//
//    public function __construct(BaseTranslationController $translationController)
//    {
//        $this->translationController = $translationController;
//    }

    public function addAction(Request $request): Response
    {
        $response = parent::addAction($request);

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


        try {
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
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $exception) {
            $contentTranslateWithLocationPattern = 'NOP';
        }

        try {
            $contentTranslateWithLocationPatternWithOutProxy = str_replace(
                '/',
                '\/?',
                urldecode(
                    $this->generateUrl(
                    // path: /content/{contentId}/location/{locationId}/translate/{toLanguageCode}/{fromLanguageCode}
                        'ibexa.content.translate_with_location',
                        [
                            'contentId'        => '([0-9]*)',
                            'locationId'         => '([0-9]*)',
                            'fromLanguageCode' => '([a-zA-Z-]*)',
                            'toLanguageCode'   => '([a-zA-Z-]*)',
                        ]
                    )
                )
            );
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $exception) {
            $contentTranslateWithLocationPatternWithOutProxy = 'NOP';
        }

        $serviceAlias = $request->request->get('add-translation')['translatorAlias'] ?? '';

//        dump([
//            '$targetUrl' => $targetUrl,
//            '$serviceAlias' => $serviceAlias,
//            '$contentTranslatePattern' => $contentTranslatePattern,
//            '$contentTranslateWithLocationPattern' => $contentTranslateWithLocationPattern,
//            '$contentTranslateWithLocationPatternWithOutProxy' => $contentTranslateWithLocationPatternWithOutProxy,
//        ]);

        if ('' === $serviceAlias || (
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslatePattern) &&
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslateWithLocationPattern) &&
            !$this->targetUrlContainsPattern($targetUrl, $contentTranslateWithLocationPatternWithOutProxy)
        )) {
//            dump(__LINE__);
//            die(__METHOD__);
            return $response;
        }

        $response->setTargetUrl(sprintf('%s?translatorAlias=%s', $targetUrl, $serviceAlias));

//        dump(__LINE__);
//        die(__METHOD__);
        return $response;
    }

    public function removeAction(Request $request): Response
    {
        return parent::removeAction($request);
    }

    private function targetUrlContainsPattern(string $targetUrl, string $pattern): bool
    {
        return 1 === preg_match("#{$pattern}#", $targetUrl);
    }
}
