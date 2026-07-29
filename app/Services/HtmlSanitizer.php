<?php

namespace App\Services;

/**
 * Allowlist-based HTML sanitizer for CMS / TipTap content.
 */
class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li',
        'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
        'span', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img',
    ];

    public function purify(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = str_replace("\0", '', $html);

        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $stripped = strip_tags($html, $allowed);

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="html-sanitizer-root">'.$stripped.'</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('html-sanitizer-root');
        if (! $root) {
            return strip_tags($stripped, $allowed);
        }

        $this->scrubNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private function scrubNode(\DOMNode $node): void
    {
        if ($node instanceof \DOMElement) {
            $tag = strtolower($node->tagName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($node->firstChild) {
                    $node->parentNode?->insertBefore($node->firstChild, $node);
                }
                $node->parentNode?->removeChild($node);

                return;
            }

            $removeAttrs = [];
            foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                $name = strtolower($attr->name);
                $value = $attr->value;

                if (str_starts_with($name, 'on') || $name === 'style') {
                    $removeAttrs[] = $attr->name;
                    continue;
                }

                if (in_array($name, ['href', 'src'], true)) {
                    $lower = strtolower(trim($value));
                    if (preg_match('/^(javascript|vbscript|data):/i', $lower)) {
                        $removeAttrs[] = $attr->name;
                        continue;
                    }
                }

                if ($tag === 'a' && $name === 'href') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                    if (! $node->hasAttribute('target')) {
                        // keep as-is
                    }
                }

                if ($tag === 'img' && ! in_array($name, ['src', 'alt', 'title', 'width', 'height', 'loading'], true)) {
                    $removeAttrs[] = $attr->name;
                }

                if ($tag === 'a' && ! in_array($name, ['href', 'title', 'target', 'rel'], true)) {
                    $removeAttrs[] = $attr->name;
                }
            }

            foreach ($removeAttrs as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->scrubNode($child);
        }
    }
}
