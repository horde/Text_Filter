<?php

declare(strict_types=1);

/**
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

/**
 * The space2html filter converts horizontal whitespace to HTML code.
 *
 * Parameters:
 * <pre>
 * encode     -- HTML encode the text?  Defaults to false.
 * charset    -- Charset of the text.  Defaults to UTF-8.
 * encode_all -- Replace all spaces with &nbsp;?  Defaults to false.
 * </pre>
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Mathieu Arnold <mat@mat.cc>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Space2html extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'charset' => 'UTF-8',
        'encode' => false,
        'encode_all' => false,
    ];

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess(string $text): string
    {
        if ($this->params['encode']) {
            $text = @htmlspecialchars($text, ENT_COMPAT, $this->params['charset']);
        }
        return $text;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        return [
            'replace' => [
                "\t" => '&nbsp; &nbsp; &nbsp; &nbsp; ',
                '  ' => '&nbsp; ',
            ],
        ];
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
        $text = str_replace('  ', ' &nbsp;', $text);
        if ($this->params['encode_all']) {
            $text = str_replace(' ', '&nbsp;', $text);
        }
        return $text;
    }
}
