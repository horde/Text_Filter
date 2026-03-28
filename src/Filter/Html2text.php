<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

use DOMDocument;
use DOMElement;
use DOMText;
use Exception;
use Horde\Text\Filter\Translation;
use Horde\Util\Domhtml;
use Horde\Util\HordeString;
use Horde_Text_Flowed;

/**
 * Takes HTML and converts it to formatted, plain text.
 *
 * Optional parameters to constructor:
 * <pre>
 * callback     - (callback) Callback triggered on every node. Passed the
 *                DOMDocument object and the DOMNode object. If the callback
 *                returns non-null, add this text to the output and skip further
 *                processing of the node.
 * width        - (integer) The wrapping width. Set to 0 to not wrap.
 * nestingLimit - (integer) The limit on node nesting. If empty, no limit.
 * </pre>
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Html2text extends Base
{
    /**
     * The list of links contained in the message.
     */
    protected array $linkList = [];

    /**
     * Current list indentation level.
     */
    protected int $indent = 0;

    /**
     * Current nesting level.
     */
    protected int $nestingLevel = 0;

    /**
     * Filter parameters.
     */
    protected array $params = [
        'callback' => null,
        'charset' => 'UTF-8',
        'width' => 75,
        'nestingLimit' => false,
    ];

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        $replace = [
            "\r" => '',
            "\t" => ' ',
        ];
        $regexp = [
            '/(?<!>)\n/' => ' ',
            '/\n/' => '',
        ];

        return [
            'replace' => $replace,
            'regexp' => $regexp,
        ];
    }

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess(string $text): string
    {
        $this->indent = 0;
        $this->linkList = [];

        return $text;
    }

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess(string $text): string
    {
        try {
            $dom = new Domhtml($text, $this->params['charset']);
            // Add two to take into account the <html> and <body> nodes.
            if (!empty($this->params['nestingLimit'])) {
                $this->params['nestingLimit'] += 2;
            }
            $text = HordeString::convertCharset($this->processNode($dom->dom, $dom->dom), 'UTF-8', $this->params['charset']);
        } catch (Exception) {
            $text = strip_tags(preg_replace("/\<br\s*\/?\>/i", "\n", $text));
        }

        /* Bring down number of empty lines to 2 max, and remove trailing
         * ws. */
        $text = preg_replace(
            ["/\s*\n{3,}/", "/ +\n/"],
            ["\n\n", "\n"],
            $text
        );

        /* Wrap the text to a readable format. */
        if ($this->params['width']) {
            $text = wordwrap($text, $this->params['width']);
        }

        /* Add link list. */
        if (!empty($this->linkList)) {
            $text .= "\n\n" . Translation::t("Links") . ":\n"
                . str_repeat('-', HordeString::length(Translation::t("Links")) + 1) . "\n";
            foreach ($this->linkList as $key => $val) {
                $text .= '[' . ($key + 1) . '] ' . $val . "\n";
            }
        }

        return ltrim(rtrim($text), "\n");
    }

    /**
     * Process DOM node.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMElement $node  Element node.
     *
     * @return string  The plaintext representation.
     */
    protected function processNode(DOMDocument $doc, DOMDocument|DOMElement $node): string
    {
        $out = '';
        if (!empty($this->params['nestingLimit']) && $this->nestingLevel > $this->params['nestingLimit']) {
            $this->nestingLevel--;
            return '';
        }
        $this->nestingLevel++;

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($this->params['callback']
                    && ($txt = call_user_func($this->params['callback'], $doc, $child)) !== null) {
                    $out .= $txt;
                    continue;
                }

                if ($child instanceof DOMElement) {
                    switch (HordeString::lower($child->tagName)) {
                        case 'h1':
                        case 'h2':
                        case 'h3':
                            $out .= "\n\n"
                                . strtoupper($this->processNode($doc, $child))
                                . "\n\n";
                            break;

                        case 'h4':
                        case 'h5':
                        case 'h6':
                            $out .= "\n\n"
                                . ucwords($this->processNode($doc, $child))
                                . "\n\n";
                            break;

                        case 'b':
                        case 'strong':
                            $out .= strtoupper($this->processNode($doc, $child));
                            break;

                        case 'u':
                            $out .= '_' . $this->processNode($doc, $child) . '_';
                            break;

                        case 'em':
                        case 'i':
                            $out .= '/' . $this->processNode($doc, $child) . '/';
                            break;

                        case 'hr':
                            $out .= "\n-------------------------\n";
                            break;

                        case 'ol':
                        case 'ul':
                        case 'dl':
                            ++$this->indent;
                            $out .= "\n" . $this->processNode($doc, $child) . "\n";
                            --$this->indent;
                            break;

                        case 'p':
                            if ($tmp = $this->processNode($doc, $child)) {
                                if (!strspn(substr($out, -2), "\n")) {
                                    $out .= "\n";
                                }

                                if (strlen(trim($tmp))) {
                                    $out .= $tmp . "\n";
                                }
                            }
                            break;

                        case 'table':
                            if ($tmp = $this->processNode($doc, $child)) {
                                $out .= "\n\n" . $tmp . "\n\n";
                            }
                            break;

                        case 'tr':
                            $out .= "\n  " . trim($this->processNode($doc, $child));
                            break;

                        case 'th':
                            $out .= strtoupper($this->processNode($doc, $child)) . " \t";
                            break;

                        case 'td':
                            $out .= $this->processNode($doc, $child) . " \t";
                            break;

                        case 'li':
                        case 'dd':
                        case 'dt':
                            $out .= "\n" . str_repeat('  ', $this->indent) . '* ' . $this->processNode($doc, $child);
                            break;

                        case 'a':
                            $out .= $this->processNode($doc, $child) . $this->buildLinkList($doc, $child);
                            break;

                        case 'blockquote':
                            $tmp = trim(preg_replace('/\s*\n{3,}/', "\n\n", $this->processNode($doc, $child)));
                            if (class_exists('Horde_Text_Flowed')) {
                                $flowed = new Horde_Text_Flowed($tmp, $this->params['charset']);
                                $flowed->setMaxLength($this->params['width']);
                                $flowed->setOptLength($this->params['width']);
                                $tmp = $flowed->toFlowed(true);
                            }
                            if (!strspn(substr($out, -1), " \r\n\t")) {
                                $out .= "\n";
                            }
                            $out .= "\n" . rtrim($tmp) . "\n\n";
                            break;

                        case 'div':
                            $out .= $this->processNode($doc, $child) . "\n";
                            break;

                        case 'br':
                            $out .= "\n";
                            break;

                        default:
                            $out .= $this->processNode($doc, $child);
                            break;
                    }
                } elseif ($child instanceof DOMText) {
                    $out .= strspn(substr($out, -1), " \r\n\t")
                        ? ltrim($child->textContent)
                        : $child->textContent;
                }
            }
        }

        if (!empty($this->params['nestingLimit'])) {
            $this->nestingLevel--;
        }

        return $out;
    }

    /**
     * Maintains an internal list of links to be displayed at the end
     * of the text, with numeric indices to the original point in the
     * text they appeared.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMElement $node  Element node.
     *
     * @return string  The link reference marker.
     */
    protected function buildLinkList(DOMDocument $doc, DOMElement $node): string
    {
        $link = $node->getAttribute('href');
        $display = $node->textContent;

        $parsed_link = parse_url($link);
        $parsed_display = @parse_url($display);

        if (isset($parsed_link['path'])) {
            $parsed_link['path'] = trim($parsed_link['path'], '/');
            if (!strlen($parsed_link['path'])) {
                unset($parsed_link['path']);
            }
        }

        if (isset($parsed_display['path'])) {
            $parsed_display['path'] = trim($parsed_display['path'], '/');
            if (!strlen($parsed_display['path'])) {
                unset($parsed_display['path']);
            }
        }

        if (((!isset($parsed_link['host'])
              && !isset($parsed_display['host']))
             || (isset($parsed_link['host'])
              && isset($parsed_display['host'])
              && $parsed_link['host'] === $parsed_display['host']))
            && ((!isset($parsed_link['path'])
              && !isset($parsed_display['path']))
             || (isset($parsed_link['path'])
              && isset($parsed_display['path'])
              && $parsed_link['path'] === $parsed_display['path']))) {
            return '';
        }

        if (($pos = array_search($link, $this->linkList)) === false) {
            $this->linkList[] = $link;
            $pos = count($this->linkList) - 1;
        }

        return '[' . ($pos + 1) . ']';
    }
}
