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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Command;

use EzSystems\EzPlatformAutomatedTranslation\ClientProvider;
use EzSystems\EzPlatformAutomatedTranslation\RepositoryAware;
use EzSystems\EzPlatformAutomatedTranslation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TranslateContentCommand.
 */
class TranslateContentCommand extends Command
{
    use RepositoryAware;
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * TranslateContentCommand constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator, ClientProvider $clientProvider)
    {
        $this->clientProvider = $clientProvider;
        parent::__construct();
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $contentId = (int) $input->getArgument('contentId');
        $content   = $this->repository->getContentService()->loadContent($contentId);
        $draft     = $this->translator->getTranslatedContent(
            $input->getOption('from'),
            $input->getOption('to'),
            $input->getArgument('service'),
            $content
        );
        $this->repository->getContentService()->publishVersion($draft->versionInfo);
        $output->writeln("Translation to {$contentId} Done.");
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->repository->getPermissionResolver()->setCurrentUserReference(
            $this->repository->getUserService()->loadUser(14)
        );
    }
}
