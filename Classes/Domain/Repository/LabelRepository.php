<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Domain\Repository;

use SourceBroker\Translatr\Domain\Model\Label;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Label>
 */
class LabelRepository extends Repository
{
    public const TABLE = 'tx_translatr_domain_model_label';

    public function findIndexedLabel(Label $label): ?Label
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored([
            'starttime',
            'endtime',
        ]);

        return $query->matching($query->logicalAnd(
            $query->equals('language', $label->getLanguage()),
            $query->equals('llFile', $label->getLlFile()),
            $query->equals('ukey', $label->getUkey()),
        ))->execute()->getFirst();
    }

    /**
     * @param array<string, mixed> $defaultLabel
     */
    public function createLanguageChildFromDefault(
        array $defaultLabel,
        string $translationFromFile,
        string $language
    ): void {
        $label = new Label();
        $label->setPid(0);
        $label->setExtension($defaultLabel['extension']);
        $label->setText($translationFromFile);
        $label->setUkey($defaultLabel['ukey']);
        $label->setLlFile($defaultLabel['ll_file']);
        $label->setLlFileIndex(strrev($defaultLabel['ll_file']));
        $label->setLanguage($language);
        $label->setModify(0);
        $label->setTags($defaultLabel['tags']);
        $this->add($label);
    }
}
