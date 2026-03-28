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
 * Converts tabs into spaces.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Tabs2spaces extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'breakchar' => "\n",
        'tabstop' => 8,
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
        $lines = explode($this->params['breakchar'], $text);
        for ($i = 0, $l = count($lines); $i < $l; ++$i) {
            while (($pos = strpos($lines[$i], "\t")) !== false) {
                $new_str = str_repeat(' ', $this->params['tabstop'] - ($pos % $this->params['tabstop']));
                $lines[$i] = substr_replace($lines[$i], $new_str, $pos, 1);
            }
        }
        return implode("\n", $lines);
    }
}
