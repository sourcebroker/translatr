<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;

readonly class SourceLabelParser
{
    public function __construct(private Typo3Version $typo3Version) {}

    /** @return array<string, string> */
    public function parse(string $contents, string $source): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            if ($contents === '' || !$document->loadXML($contents, LIBXML_NONET) || $document->doctype instanceof \DOMDocumentType) {
                throw new InvalidXmlFileException('Invalid language XML: ' . $source, 1791214400);
            }
            $root = $document->documentElement;
            if (!$root instanceof \DOMElement || $root->localName !== 'xliff') {
                throw new InvalidXmlFileException('Expected XLIFF content in ' . $source, 1791214401);
            }
            $xpath = new \DOMXPath($document);
            $labels = [];
            $version = $root->getAttribute('version');
            if (str_starts_with($version, '2.')) {
                foreach ($this->elements($xpath, '//*[local-name()="unit"]') as $unit) {
                    $sources = $this->elements($xpath, './*[local-name()="segment"]/*[local-name()="source"]', $unit);
                    $texts = array_map($this->text(...), $sources);
                    $deprecated = $this->elements($xpath, './*[local-name()="segment" and @subState="deprecated"]', $unit) !== [];
                    $labels[$this->labelKey($unit->getAttribute('id'), $deprecated)] = $this->pluralText($texts);
                }
                return $labels;
            }
            if ($version !== '' && !str_starts_with($version, '1.')) {
                throw new InvalidXmlFileException('Unsupported XLIFF version in ' . $source, 1791214404);
            }
            foreach ($this->elements($xpath, '//*[local-name()="trans-unit"]') as $unit) {
                $parent = $unit->parentNode;
                if ($parent instanceof \DOMElement && $parent->getAttribute('restype') === 'x-gettext-plurals') {
                    continue;
                }
                $sources = $this->elements($xpath, './*[local-name()="source"]', $unit);
                $labels[$this->labelKey($unit->getAttribute('id'), $unit->hasAttribute('x-unused-since'))] = isset($sources[0]) ? $this->text($sources[0]) : '';
            }
            foreach ($this->elements($xpath, '//*[local-name()="group" and @restype="x-gettext-plurals"]') as $group) {
                $forms = [];
                $deprecated = false;
                $key = $group->getAttribute('id');
                foreach ($this->elements($xpath, './*[local-name()="trans-unit"]', $group) as $unit) {
                    if (!preg_match('/^(.*)\[(\d+)\]$/', $unit->getAttribute('id'), $matches)) {
                        continue;
                    }
                    $key = $key !== '' ? $key : $matches[1];
                    $deprecated = $deprecated || $unit->hasAttribute('x-unused-since');
                    $sources = $this->elements($xpath, './*[local-name()="source"]', $unit);
                    $forms[(int)$matches[2]] = isset($sources[0]) ? $this->text($sources[0]) : '';
                }
                ksort($forms);
                $labels[$this->labelKey($key, $deprecated)] = $this->pluralText($forms);
            }
            return $labels;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    /** @return list<\DOMElement> */
    private function elements(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): array
    {
        $elements = [];
        foreach ($xpath->query($expression, $context) ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }
        return $elements;
    }

    private function text(\DOMElement $source): string
    {
        // Preserve the runtime's text representation without depending on its internal parser classes.
        if ($this->typo3Version->getMajorVersion() < 14) {
            return $source->textContent;
        }
        for ($node = $source; $node instanceof \DOMElement; $node = $node->parentNode) {
            if ($node->hasAttributeNS('http://www.w3.org/XML/1998/namespace', 'space')) {
                if ($node->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'space') === 'preserve') {
                    return $source->textContent;
                }
                break;
            }
        }
        return trim((string)preg_replace('/\s+/', ' ', $source->textContent));
    }

    /** @param array<int, string> $forms */
    private function pluralText(array $forms): string
    {
        // TYPO3 13 uses the first source form; TYPO3 14 expects ICU plural syntax.
        if ($this->typo3Version->getMajorVersion() < 14 || count($forms) < 2) {
            return $forms[0] ?? '';
        }
        $forms = array_values($forms);
        return '{0, plural, one {' . $forms[0] . '} other {' . $forms[1] . '}}';
    }

    private function labelKey(string $key, bool $deprecated): string
    {
        return $key . ($deprecated && $this->typo3Version->getMajorVersion() >= 14 ? '.x-unused' : '');
    }
}
