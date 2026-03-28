<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

use DOMComment;
use DOMElement;
use Horde\Util\Domhtml;
use Horde\Util\HordeString;
use Exception;

/**
 * This filter attempts to make HTML safe for viewing. IT IS NOT PERFECT. If
 * you enable HTML viewing, you are opening a security hole.
 *
 * Filter parameters:
 *   - charset: (string) The charset of the text.
 *              DEFAULT: UTF-8
 *   - noprefetch: (boolean) Disable DNS pre-fetching? See:
 *                 https://developer.mozilla.org/En/Controlling_DNS_prefetching
 *                 DEFAULT: false
 *   - return_document: (boolean) If true, returns a full HTML representation of
 *                      the document.
 *                      DEFAULT: false (returns the contents contained inside
 *                               the BODY tag)
 *   - return_dom: (boolean) If true, return a Domhtml object instead of
 *                 HTML text (overrides return_document).
 *                 DEFAULT: false
 *   - strip_styles: (boolean) Strip style tags?
 *                   DEFAULT: true
 *   - strip_style_attributes: (boolean) Strip style attributes in all tags?
 *                             DEFAULT: true
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Xss extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'charset' => 'UTF-8',
        'noprefetch' => false,
        'return_document' => false,
        'return_dom' => false,
        'strip_styles' => true,
        'strip_style_attributes' => true,
    ];

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string|Domhtml  The modified text or a Domhtml object if
     *                               the 'return_dom' parameter is set.
     * @throws Exception
     */
    public function postProcess(string $text): string|Domhtml
    {
        $dom = new Domhtml($text, $this->params['charset']);

        foreach ($dom as $node) {
            $this->processNode($node);
        }

        if ($this->params['noprefetch']) {
            $meta = $dom->dom->createElement('meta');
            $meta->setAttribute('http-equiv', 'x-dns-prefetch-control');
            $meta->setAttribute('value-equiv', 'off');

            $head = $dom->getHead();
            $head->appendChild($meta);
        }

        if ($this->params['return_dom']) {
            return $dom;
        }

        return $this->params['return_document']
            ? $dom->returnHtml()
            : $dom->returnBody();
    }

    /**
     * Process DOM node.
     *
     * @param DOMElement $node  Element node.
     */
    protected function processNode(DOMElement|DOMComment $node): void
    {
        if ($node instanceof DOMElement) {
            $remove = $this->params['strip_style_attributes']
                ? ['style']
                : [];

            switch (HordeString::lower($node->tagName)) {
                case 'a':
                case 'form':
                    /* Strip out data URLs living in link-like elements
                     * (Bug #8715). */
                    if (HordeString::lower($node->tagName) === 'form') {
                        $attributes = ['action'];
                    } else {
                        $attributes = ['href', 'xlink:href'];
                    }
                    foreach ($attributes as $attribute) {
                        if ($node->hasAttribute($attribute)
                            && preg_match("/\s*data:/i", $node->getAttribute($attribute))) {
                            $remove[] = $attribute;
                        }
                    }
                    break;

                case 'applet':
                case 'audio':
                case 'bgsound':
                case 'embed':
                case 'iframe':
                case 'import':
                case 'java':
                case 'layer':
                case 'meta':
                case 'object':
                case 'script':
                case 'video':
                case 'xml':
                    /* Remove all tags that might cause trouble. */
                    $node->parentNode->removeChild($node);
                    break;

                case 'base':
                case 'link':
                case 'style':
                    /* We primarily strip out <base> tags due to styling
                     * concerns. There is a security issue with HREF tags,
                     * but the 'javascript' search/replace code
                     * sufficiently filters these strings. */
                    if ($this->params['strip_styles']) {
                        $node->parentNode->removeChild($node);
                    }
                    break;

                case 'html':
                    if ($node->hasAttribute('manifest')) {
                        $remove[] = 'manifest';
                    }
                    break;

                case 'set':
                    /* I believe this attack only works on old browsers.
                     * But makes no sense allowing HTML to try to set
                     * innerHTML anyway. */
                    if ($node->hasAttribute('attributename')
                        && (strcasecmp($node->getAttribute('attributename'), 'innerHTML') === 0)) {
                        $node->parentNode->removeChild($node);
                    }
                    break;
            }

            foreach ($node->attributes as $val) {
                /* Never allow on<foo>="bar()",
                 * attribute="[mocha|*script]:foo()", or
                 * attribute="&{...}". */
                if ((stripos(ltrim($val->name), 'on') === 0)
                    || preg_match("/^\s*(?:mocha:|[^:]+script:|&{)/i", $val->value)) {
                    $remove[] = $val->name;
                }
            }

            foreach ($remove as $val) {
                $node->removeAttribute($val);
            }
        } elseif ($node instanceof DOMComment) {
            /* Remove HTML comments (including some scripts &
             * styles). */
            if ($this->params['strip_styles']) {
                $node->parentNode->removeChild($node);
            }
        }
    }
}
