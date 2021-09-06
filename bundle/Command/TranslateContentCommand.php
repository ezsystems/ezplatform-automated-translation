<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Command;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use EzSystems\EzPlatformAutomatedTranslation\ClientProvider;
use EzSystems\EzPlatformAutomatedTranslation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TranslateContentCommand extends Command
{
    private const ADMINISTRATOR_USER_ID = 14;

    /** @var Translator */
    private $translator;

    /** @var ClientProvider */
    private $clientProvider;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    public function __construct(
        Translator $translator,
        ClientProvider $clientProvider,
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService
    ) {
        $this->clientProvider = $clientProvider;
        $this->translator = $translator;
        $this->contentService = $contentService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ezplatform:automated:translate')
            ->setAliases(['eztranslate'])
            ->setDescription('Translate a Content in a new Language')
            ->addArgument('contentId', InputArgument::REQUIRED, 'ContentId')
            ->addArgument(
                'service',
                InputArgument::REQUIRED,
                'Remote Service for Translation. <comment>[' .
                implode(' ', array_keys($this->clientProvider->getClients())) . ']</comment>'
            )
            ->addOption('from', '--from', InputOption::VALUE_REQUIRED, 'Source Language')
            ->addOption('to', '--to', InputOption::VALUE_REQUIRED, 'Target Language');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contentId = (int) $input->getArgument('contentId');
        $content = $this->contentService->loadContent($contentId);
        $draft = $this->translator->getTranslatedContent(
            $input->getOption('from'),
            $input->getOption('to'),
            $input->getArgument('service'),
            $content
        );
        $this->contentService->publishVersion($draft->versionInfo);
        $output->writeln("Translation to {$contentId} Done.");

        return Command::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUser(self::ADMINISTRATOR_USER_ID)
        );
    }
}
