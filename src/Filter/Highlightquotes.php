<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

use Horde\Util\HordeString;

/**
 * Highlights quoted messages with different colors for the different quoting
 * levels.
 *
 * CSS class names called "quoted1" ... "quoted{$cssLevels}" must be present.
 *
 * The text to be passed in must have already been passed through
 * htmlspecialchars().
 *
 * Parameters:
 * <pre>
 * 'citeblock'  -- Display cite blocks?
 *                 DEFAULT: true
 * 'cssLevels'  -- Number of defined CSS class names.
 *                 DEFAULT: 5
 * 'hideBlocks' -- Hide large quoted text blocks by default?
 *                 DEFAULT: false
 * </pre>
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Highlightquotes extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'citeblock' => true,
        'cssLevels' => 5,
        'hideBlocks' => false,
    ];

    /**
     * The number of quoted lines to exceed to trigger large block
     * processing.
     */
    protected int $qlimit = 8;

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess(string $text): string
    {
        /* Tack a newline onto the beginning of the string so that we
         * correctly highlight when the first character in the string is a
         * quote character. */
        return "\n$text";
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        /* Remove extra spaces before quoted text as the CSS formatting will
         * automatically add a bit of space for us. */
        return ($this->params['citeblock'])
            ? ['regexp' => ["/<br \/>\s*\n\s*<br \/>\s*\n\s*((&gt;\s?)+)/m" => "<br />\n\\1"]]
            : [];
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
        /* Use cite blocks to display the different quoting levels? */
        $cb = $this->params['citeblock'];

        /* Cite level before parsing the current line. */
        $qlevel = 0;

        /* Other loop variables. */
        $text_out = '';
        $lines = [];
        $tmp = ['level' => 0, 'lines' => []];
        $qcount = 0;

        /* Parse text line by line. */
        foreach (explode("\n", $text) as $line) {
            /* Cite level of current line. */
            $clevel = 0;
            $matches = [];

            /* Do we have a citation line? */
            if (preg_match('/^\s*((&gt;\s?)+)/m', $line, $matches)) {
                /* Count number of > characters => cite level */
                $clevel = count(preg_split('/&gt;\s?/', $matches[1])) - 1;
            }

            if ($cb && isset($matches[1])) {
                /* Strip all > characters. */
                $line = substr($line, HordeString::length($matches[1]));
            }

            /* Is this cite level lower than the current level? */
            if ($clevel < $qlevel) {
                $lines[] = $tmp;
                if ($clevel === 0) {
                    $text_out .= $this->process($lines, $qcount);
                    $lines = [];
                    $qcount = 0;
                }
                $tmp = ['level' => $clevel, 'lines' => []];

                /* Is this cite level higher than the current level? */
            } elseif ($clevel > $qlevel) {
                $lines[] = $tmp;
                $tmp = ['level' => $clevel, 'lines' => []];
            }

            $tmp['lines'][] = $line;
            $qlevel = $clevel;

            if ($qlevel) {
                ++$qcount;
            }
        }

        $lines[] = $tmp;
        $text_out .= $this->process($lines, $qcount);

        /* Remove the leading newline we added above, if it's still there. */
        return ($text_out[0] === "\n")
            ? substr($text_out, 1)
            : $text_out;
    }

    /**
     * Process a batch of lines at the same quoted level.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  The rendered lines.
     */
    protected function process(array $lines, int $qcount): string
    {
        $curr = reset($lines);
        $out = implode("\n", $this->removeBr($curr['lines']));

        if ($qcount > $this->qlimit) {
            $out .= $this->beginLargeBlock($lines, $qcount);
        }

        $level = 0;

        while ($curr = next($lines)) {
            if ($level > $curr['level']) {
                for ($i = $level; $i > $curr['level']; --$i) {
                    $out .= $this->params['citeblock'] ? '</div>' : '</font>';
                }
            } else {
                for ($i = $level; $i < $curr['level']; ++$i) {
                    /* Add quote block start tags for each cite level. */
                    $out .= ($this->params['citeblock'] ? '<div class="citation ' : '<font class="')
                        . 'quoted' . (($i % $this->params['cssLevels']) + 1) . '"'
                        . ((($i === 0) && ($qcount > $this->qlimit) && $this->params['hideBlocks']) ? ' style="display:none"' : '')
                        . '>';
                }
            }

            $out .= implode("\n", $this->removeBr($curr['lines']));
            $level = $curr['level'];
        }

        for ($i = $level; $i > 0; --$i) {
            $out .= $this->params['citeblock'] ? '</div>' : '</font>';
        }

        if ($qcount > $this->qlimit) {
            $out .= $this->endLargeBlock($lines, $qcount);
        }

        return $out;
    }

    /**
     * Add HTML code at the beginning of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function beginLargeBlock(array $lines, int $qcount): string
    {
        return '';
    }

    /**
     * Add HTML code at the end of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function endLargeBlock(array $lines, int $qcount): string
    {
        return '';
    }

    /**
     * Remove leading and trailing BR tags.
     *
     * @param array $lines  An array of text.
     *
     * @return array  The array with bare BR tags removed at the beginning and
     *                end.
     */
    protected function removeBr(array $lines): array
    {
        /* Remove leading/trailing line breaks. Spacing between quote blocks
         * will be handled by div CSS. */
        if (!$this->params['citeblock']) {
            return $lines;
        }

        foreach (array_keys($lines) as $i) {
            if (!preg_match("/^\s*<br\s*\/>\s*$/i", $lines[$i])) {
                break;
            }
            unset($lines[$i]);
        }

        foreach (array_reverse(array_keys($lines)) as $i) {
            if (!preg_match("/^\s*<br\s*\/>\s*$/i", $lines[$i])) {
                break;
            }
            unset($lines[$i]);
        }

        return $lines;
    }
}
