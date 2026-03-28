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
 * Filters the given text based on the words found in a word list.
 *
 * Parameters:
 *   - replacement: (string) The replacement string. Defaults to "*****".
 *   - words: (array) List of words to replace.
 *   - words_file: (string) Filename containing the words to replace.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2004-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Text_Filter
 */
class Words extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'replacement' => '*****',
    ];

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        $regexp = [];
        $words = [];

        if (isset($this->params['words_file'])
            && is_readable($this->params['words_file'])) {
            /* Read the file and iterate through the lines. */
            $lines = file($this->params['words_file']);
            foreach ($lines as $line) {
                /* Strip whitespace and comments. */
                $words[] = preg_replace('|#.*$|', '', trim($line));
            }
        }

        if (isset($this->params['words'])) {
            $words = array_merge(
                $words,
                array_map('trim', $this->params['words'])
            );
        }

        foreach ($words as $val) {
            if (strlen($val)) {
                $regexp["/(\b(\w*)$val\b|\b$val(\w*)\b)/i"] = $this->getReplacement($val);
            }
        }

        return ['regexp' => $regexp];
    }

    /**
     * Returns the replacement string for a word.
     *
     * @param string $line  The word to replace.
     *
     * @return string  The replacement string.
     */
    protected function getReplacement(string $line): string
    {
        return $this->params['replacement']
            ? $this->params['replacement']
            : substr($line, 0, 1) . str_repeat('*', strlen($line) - 1);
    }
}
