<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

/**
 * Removes some common entities and high-ascii or otherwise nonstandard
 * characters common in text pasted from Microsoft Word into a browser.
 *
 * This function should NOT be used on non-ASCII text; it may and probably
 * will butcher other character sets indiscriminately.  Use it only to clean
 * US-ASCII (7-bit) text which you suspect (or know) may have invalid or
 * non-printing characters in it.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Cleanascii extends Base
{
    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess(string $text): string
    {
        if (preg_match('/|([^#]*)#.*/', $text, $regs)) {
            $text = $regs[1];

            if (!empty($text)) {
                $text = $text . "\n";
            }
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
        /* Remove control characters. */
        $regexp = ['/[\x00-\x1f]+/' => ''];

        /* The '�' entry may look wrong, depending on your editor,
         * but it's not - that's not really a single quote.
         * Note: Legacy MS Word chars may appear as � in UTF-8 causing duplicate keys. */
        // phpcs:disable
        /** @var array<string,string> $replace */
        $replace = [ // @phpstan-ignore-line array.duplicateKey
            chr(150) => '-',
            chr(167) => '*',
            '��' => '*',
            '�' => '...',
            '�' => "'",
            '�' => "'",
            '�' => '"',
            '�' => '"',
            '�' => '*',
            '�' => '-',
            '�' => '-',
            '�' => '*',
            '&#61479;' => '.',
            '&#61572;' => '*',
            '&#61594;' => '*',
            '&#61640;' => '-',
            '&#61623;' => '-',
            '&#61607;' => '*',
            '&#61553;' => '*',
            '&#61558;' => '*',
            '&#8226;' => '*',
            '&#9658;' => '>',
        ];

        return ['regexp' => $regexp, 'replace' => $replace];
    }
}
