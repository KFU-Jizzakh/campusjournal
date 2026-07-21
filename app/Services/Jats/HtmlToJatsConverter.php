<?php

namespace App\Services\Jats;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * PURPOSE: Converts article body HTML to a JATS body subtree
 * by traversing the DOM and mapping elements to JATS equivalents.
 *
 * SPECIFICATION: SPEC-10/AC-6
 */
class HtmlToJatsConverter
{
    public function convert(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '<body/>';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"?><root>'.$this->prepare($html).'</root>';

        libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $root = $dom->getElementsByTagName('root')->item(0);
        if (! $root instanceof DOMElement) {
            return '<body/>';
        }

        $out = new DOMDocument('1.0', 'UTF-8');
        $body = $out->createElement('body');
        $out->appendChild($body);

        $this->convertBlockSequence($root, $body, $out);

        if (! $body->hasChildNodes()) {
            return '<body/>';
        }

        $this->wrapInlineChildren($body, $out);

        return $out->saveXML($body);
    }

    private function prepare(string $html): string
    {
        return str_replace('&nbsp;', ' ', $html);
    }

    /**
     * Walk top-level children while maintaining a section stack so that
     * <hN> headings produce hierarchical <sec> nesting.
     */
    private function convertBlockSequence(DOMNode $source, DOMElement $body, DOMDocument $out): void
    {
        /** @var array<int, array{level:int, sec:DOMElement}> $stack */
        $stack = [];

        foreach (iterator_to_array($source->childNodes) as $child) {
            $level = $this->headingLevel($child);
            if ($level !== null) {
                while (! empty($stack) && end($stack)['level'] >= $level) {
                    array_pop($stack);
                }
                $parent = empty($stack) ? $body : end($stack)['sec'];
                $sec = $out->createElement('sec');
                $parent->appendChild($sec);
                $title = $out->createElement('title');
                $sec->appendChild($title);
                /** @var DOMElement $child */
                $this->convertInline($child, $title, $out);
                $stack[] = ['level' => $level, 'sec' => $sec];

                continue;
            }

            $target = empty($stack) ? $body : end($stack)['sec'];
            $this->convertNode($child, $target, $out);
        }
    }

    private function headingLevel(DOMNode $node): ?int
    {
        if (! $node instanceof DOMElement) {
            return null;
        }
        $name = strtolower($node->nodeName);
        if (preg_match('/^h([1-6])$/', $name, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function convertChildren(DOMNode $source, DOMElement $target, DOMDocument $out): void
    {
        foreach (iterator_to_array($source->childNodes) as $child) {
            $this->convertNode($child, $target, $out);
        }
    }

    private function convertNode(DOMNode $node, DOMElement $target, DOMDocument $out): void
    {
        if ($node instanceof DOMText) {
            $text = $node->nodeValue ?? '';
            if ($text !== '') {
                $target->appendChild($out->createTextNode($text));
            }

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->nodeName);

        switch ($tag) {
            case 'p':
                $p = $out->createElement('p');
                $target->appendChild($p);
                $this->convertInline($node, $p, $out);

                return;

            case 'ul':
            case 'ol':
                $list = $out->createElement('list');
                $list->setAttribute('list-type', $tag === 'ol' ? 'order' : 'bullet');
                $target->appendChild($list);
                foreach (iterator_to_array($node->childNodes) as $li) {
                    if ($li instanceof DOMElement && strtolower($li->nodeName) === 'li') {
                        $item = $out->createElement('list-item');
                        $list->appendChild($item);
                        $p = $out->createElement('p');
                        $item->appendChild($p);
                        $this->convertInline($li, $p, $out);
                    }
                }

                return;

            case 'blockquote':
                $q = $out->createElement('disp-quote');
                $target->appendChild($q);
                $this->convertChildren($node, $q, $out);

                return;

            case 'table':
                $wrap = $out->createElement('table-wrap');
                $target->appendChild($wrap);
                $copied = $out->importNode($node, true);
                $wrap->appendChild($copied);

                return;

            default:
                if ($this->mapInline($node, $target, $out)) {
                    return;
                }

                // Unknown block-level: unwrap and render children directly.
                $this->convertChildren($node, $target, $out);
        }
    }

    private function convertInline(DOMNode $source, DOMElement $target, DOMDocument $out): void
    {
        foreach (iterator_to_array($source->childNodes) as $child) {
            if ($child instanceof DOMText) {
                $target->appendChild($out->createTextNode($child->nodeValue ?? ''));

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if ($this->mapInline($child, $target, $out)) {
                continue;
            }

            // Unknown inline: unwrap descendants.
            $this->convertInline($child, $target, $out);
        }
    }

    /**
     * Handle inline-level tags shared between block and inline contexts.
     * Returns true if the tag was recognised and emitted.
     */
    private function mapInline(DOMElement $node, DOMElement $target, DOMDocument $out): bool
    {
        $tag = strtolower($node->nodeName);

        switch ($tag) {
            case 'strong':
            case 'b':
                $el = $out->createElement('bold');
                $target->appendChild($el);
                $this->convertInline($node, $el, $out);

                return true;

            case 'em':
            case 'i':
                $el = $out->createElement('italic');
                $target->appendChild($el);
                $this->convertInline($node, $el, $out);

                return true;

            case 'a':
                $el = $out->createElement('ext-link');
                $el->setAttribute('ext-link-type', 'uri');
                if ($node->hasAttribute('href')) {
                    $el->setAttribute('xlink:href', $node->getAttribute('href'));
                }
                $target->appendChild($el);
                $this->convertInline($node, $el, $out);

                return true;

            case 'img':
                $g = $out->createElement('graphic');
                if ($node->hasAttribute('src')) {
                    $g->setAttribute('xlink:href', $node->getAttribute('src'));
                }
                $target->appendChild($g);

                return true;

            case 'br':
                $target->appendChild($out->createTextNode(' '));

                return true;
        }

        return false;
    }

    private function wrapInlineChildren(DOMElement $body, DOMDocument $out): void
    {
        $hasBlock = false;
        foreach ($body->childNodes as $c) {
            if ($c instanceof DOMElement && in_array($c->nodeName, ['p', 'sec', 'list', 'disp-quote', 'table-wrap', 'graphic'], true)) {
                $hasBlock = true;
                break;
            }
        }

        if ($hasBlock) {
            return;
        }

        $p = $out->createElement('p');
        while ($body->firstChild) {
            $p->appendChild($body->firstChild);
        }
        $body->appendChild($p);
    }
}
