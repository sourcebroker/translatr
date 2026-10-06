<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

final readonly class XlfBuilder
{
    /**
     * @param list<array<string, mixed>> $labels
     */
    public function build(array $labels, string $languageCode = 'default'): \DOMDocument
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $root = $xml->createElement('xliff');
        $xml->appendChild($root);
        $root->setAttribute('version', '1.0');

        $file = $xml->createElement('file');
        $root->appendChild($file);
        $file->setAttribute('source-language', 'en');
        if ($languageCode !== 'default') {
            $file->setAttribute('target-language', $languageCode);
        }
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('date', (new \DateTime())->format('c'));
        $file->setAttribute('product', '');

        $file->appendChild($xml->createElement('header'));
        $fileBody = $xml->createElement('body');
        $file->appendChild($fileBody);

        foreach ($labels as $label) {
            $transUnit = $xml->createElement('trans-unit');
            $transUnit->setAttribute('id', (string)$label['ukey']);
            $translation = $xml->createElement($label['isocode'] === 'default' ? 'source' : 'target');
            $translation->appendChild($xml->createCDATASection((string)$label['text']));
            $transUnit->appendChild($translation);
            $fileBody->appendChild($transUnit);
        }

        return $xml;
    }
}
