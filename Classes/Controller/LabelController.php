<?php

namespace SourceBroker\Translatr\Controller;

use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use Psr\Http\Message\ResponseInterface;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use SourceBroker\Translatr\Domain\Repository\LanguageRepository;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class LabelController extends ActionController
{

    private ModuleTemplate $moduleTemplate;
    protected ?ModuleData $moduleData = null;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly LabelRepository $labelRepository,
        protected readonly LanguageRepository $languageRepository,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly PageRenderer $pageRenderer,
    ) {
    }

    public function initializeAction(): void
    {
        $this->moduleData = $this->request->getAttribute('moduleData');
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }

    public function indexAction(?BeLabelDemand $demand = null): ResponseInterface
    {
        if (!$demand) {
            $demand = GeneralUtility::makeInstance(BeLabelDemand::class);
        }

        if ($demand->getExtension()) {
            $this->labelRepository->indexExtensionLabels($demand->getExtension());
            $GLOBALS['BE_USER']->pushModuleData('translatr/recentlySelectedModule', $demand->getExtension());
        } else {
            if(!empty($GLOBALS['BE_USER']->getModuleData('translatr/recentlySelectedModule'))) {
                $demand->setExtension($GLOBALS['BE_USER']->getModuleData('translatr/recentlySelectedModule'));
                $this->labelRepository->indexExtensionLabels($demand->getExtension());
            }
        }

        if (is_array($demand->getLanguages())) {
            $GLOBALS['BE_USER']->pushModuleData('translatr/recentlySelectedLanguages', $demand->getLanguages());
        } else {
            $demand->setLanguages(is_array($GLOBALS['BE_USER']->getModuleData('translatr/recentlySelectedLanguages'))
                ? $GLOBALS['BE_USER']->getModuleData('translatr/recentlySelectedLanguages') : []);
        }

        $this->moduleTemplate->assignMultiple([
            'labels' => $this->labelRepository->findDemandedForBe($demand),
            'extensions' => $this->labelRepository->getExtensionsItems(),
            'languages' => LanguageUtility::getAvailableLanguages(),
            'demand' => $demand,
            'id' => (int)GeneralUtility::_GET('id'),
        ]);
        return $this->moduleTemplate->renderResponse('Label/List');


    }


}
