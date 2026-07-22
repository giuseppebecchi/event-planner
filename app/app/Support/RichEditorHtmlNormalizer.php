<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMText;
use Illuminate\Support\Str;

class RichEditorHtmlNormalizer
{
    public static function normalizeListItems(string $html): string
    {
        if (trim($html) === '' || ! Str::contains($html, '<li')) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        foreach (iterator_to_array($document->getElementsByTagName('li')) as $listItem) {
            if (! $listItem instanceof DOMElement || static::hasBlockChild($listItem)) {
                continue;
            }

            static::wrapInlineChildren($document, $listItem);
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return $html;
        }

        $normalized = '';

        foreach ($body->childNodes as $child) {
            $normalized .= $document->saveHTML($child);
        }

        return trim($normalized);
    }

    protected static function hasBlockChild(DOMElement $element): bool
    {
        $blockTags = [
            'blockquote',
            'div',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'ol',
            'p',
            'pre',
            'table',
            'ul',
        ];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), $blockTags, true)) {
                return true;
            }
        }

        return false;
    }

    protected static function wrapInlineChildren(DOMDocument $document, DOMElement $listItem): void
    {
        $paragraph = $document->createElement('p');

        while ($listItem->firstChild) {
            $child = $listItem->firstChild;

            if ($child instanceof DOMText && trim($child->wholeText) === '') {
                $listItem->removeChild($child);

                continue;
            }

            $paragraph->appendChild($child);
        }

        if ($paragraph->hasChildNodes()) {
            $listItem->appendChild($paragraph);
        }
    }
}
