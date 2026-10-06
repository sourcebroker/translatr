<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Controller;

use Psr\Http\Message\ResponseInterface;
use SourceBroker\Translatr\Backend\LabelFilterState;
use SourceBroker\Translatr\Configuration\Configurator;
use SourceBroker\Translatr\Database\LabelReader;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Service\ExtensionProvider;
use SourceBroker\Translatr\Service\LabelIndexer;
use SourceBroker\Translatr\Service\LanguageService;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class LabelController extends ActionController
{
    private ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly LabelReader $labelReader,
        private readonly LabelFilterState $filterState,
        protected readonly LabelIndexer $labelIndexer,
        protected readonly ExtensionProvider $extensionProvider,
        protected readonly LanguageService $languageService,
        private readonly FormProtectionFactory $formProtectionFactory,
        private readonly Configurator $configurator,
    ) {}

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:translatr/Resources/Private/Language/locallang_label.xlf:mlang_tabs_tab') ?? 'Translate');
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }

    public function indexAction(?BeLabelDemand $demand = null): ResponseInterface
    {
        $extensions = $this->extensionProvider->getItems();
        $languages = $this->languageService->getAvailableLanguages() ?? [];
        $demand = $this->filterState->resolve($demand ?? new BeLabelDemand(), $extensions, $languages);
        if ($demand->getExtension()) {
            $this->indexSourceLabels($demand->getExtension());
        }
        return $this->renderLabels($demand);
    }

    protected function renderLabels(BeLabelDemand $demand): ResponseInterface
    {
        $extensions = $this->extensionProvider->getItems();
        $languages = $this->languageService->getAvailableLanguages() ?? [];
        $showSyncButton = $this->configurator->isManualSynchronizationEnabled() && (bool)$demand->getExtension();
        $this->moduleTemplate->assignMultiple([
            'labels' => $this->labelReader->findDemandedForBe($demand),
            'extensions' => $extensions,
            'languages' => $languages,
            'demand' => $demand,
            'showSyncButton' => $showSyncButton,
            'refreshToken' => $showSyncButton
                ? $this->formProtectionFactory->createFromRequest($this->request)->generateToken('translatr', 'refresh')
                : '',
        ]);
        return $this->moduleTemplate->renderResponse('Label/List');
    }

    /**
     * @param list<string> $languages
     */
    public function refreshAction(string $extension, array $languages = [], string $refreshToken = ''): ResponseInterface
    {
        if ($this->request->getMethod() !== 'POST') {
            return new HtmlResponse('Method not allowed', 405, ['Allow' => 'POST']);
        }
        if (!$this->configurator->isManualSynchronizationEnabled()
            || !$this->formProtectionFactory->createFromRequest($this->request)
            ->validateToken($refreshToken, 'translatr', 'refresh')
            || !array_key_exists($extension, $this->extensionProvider->getItems())
        ) {
            return new HtmlResponse('Refresh not permitted', 403);
        }

        $indexed = $this->indexSourceLabels($extension, true);
        $this->filterState->remember($extension, $languages);
        if (!$indexed) {
            // Render directly: a redirect would repeat indexing and duplicate the warning.
            $demand = new BeLabelDemand();
            $demand->setExtension($extension);
            $demand->setLanguages($languages);
            return $this->renderLabels($demand);
        }
        $this->addFlashMessage(
            LocalizationUtility::translate('LLL:EXT:translatr/Resources/Private/Language/locallang_label.xlf:labels.refreshed')
                ?? 'Labels synchronized from source files.'
        );
        return $this->redirect('index');
    }

    protected function indexSourceLabels(string $extension, bool $force = false): bool
    {
        try {
            $this->labelIndexer->index($extension, $force);
            return true;
        } catch (InvalidXmlFileException $exception) {
            $this->addFlashMessage(
                'Source labels could not be synchronized. Existing database labels remain available. ' . $exception->getMessage(),
                'Translatr',
                ContextualFeedbackSeverity::WARNING,
                false
            );
            return false;
        }
    }
}
