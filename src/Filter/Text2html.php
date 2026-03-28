<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

use Horde\Text\Filter\TextFilter;
use Horde\Util\HordeString;
use Horde_Text_Flowed;

/**
 * Turn text into HTML with varying levels of parsing.  For no html
 * whatsoever, use htmlspecialchars() instead.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Text2html extends Base
{
    public const PASSTHRU = 0;
    public const SYNTAX = 1;
    public const MICRO = 2;
    public const MICRO_LINKURL = 3;
    public const NOHTML = 4;
    public const NOHTML_NOBREAK = 5;

    /**
     * Filter parameters.
     */
    protected array $params = [
        'charset' => 'UTF-8',
        'class' => 'fixed',
        'emails' => false,
        'flowed' => '<blockquote>',
        'linkurls' => false,
        'text2html' => false,
        'parselevel' => 0,
        'space2html' => false,
        'secretManager' => null,
    ];

    /**
     * Constructor.
     *
     * @param array $params  Parameters specific to this driver.
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        // Use UTF-8 instead of US-ASCII
        if (HordeString::lower($this->params['charset']) === 'us-ascii') {
            $this->params['charset'] = 'utf-8';
        }
    }

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string|Horde_Text_Flowed $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess(string|Horde_Text_Flowed $text): string
    {
        if ($text instanceof Horde_Text_Flowed) {
            $text->setMaxLength(0);
            $lines = $text->toFixedArray();
            $level = 0;
            $out = '';
            $txt = '';

            foreach ($lines as $key => $val) {
                $line = ltrim($val['text'], '>');

                if (!isset($lines[$key + 1])) {
                    $out .= $this->preProcess(ltrim($txt) . $line);
                    while (--$level > 0) {
                        $out .= '</blockquote>';
                    }
                } elseif ($val['level'] > $level) {
                    $out .= $this->preProcess(ltrim($txt));
                    do {
                        $out .= $this->params['flowed'];
                    } while (++$level !== $val['level']);
                    $txt = $line;
                } elseif ($val['level'] < $level) {
                    $out .= $this->preProcess(ltrim($txt));
                    do {
                        $out .= '</blockquote>';
                    } while (--$level !== $val['level']);
                    $txt = $line;
                } else {
                    $txt .= "\n" . $line;
                }
            }

            return $out;
        }

        if (!strlen($text)) {
            return '';
        }

        /* Abort out on simple cases. */
        if ($this->params['parselevel'] === self::PASSTHRU) {
            return $text;
        }

        if ($this->params['parselevel'] === self::NOHTML_NOBREAK) {
            return @htmlspecialchars($text, ENT_COMPAT, $this->params['charset']);
        }

        if ($this->params['parselevel'] < self::NOHTML) {
            $filters = [];
            if ($this->params['linkurls']) {
                reset($this->params['linkurls']);
                $this->params['linkurls'][key($this->params['linkurls'])]['encode'] = true;
                $filters = $this->params['linkurls'];
            } else {
                $filters['linkurls'] = [
                    'encode' => true,
                    'secretManager' => $this->params['secretManager'],
                ];
            }

            if ($this->params['parselevel'] < self::MICRO_LINKURL) {
                if ($this->params['emails']) {
                    reset($this->params['emails']);
                    $this->params['emails'][key($this->params['emails'])]['encode'] = true;
                    $filters += $this->params['emails'];
                } else {
                    $filters['emails'] = [
                        'encode' => true,
                        'secretManager' => $this->params['secretManager'],
                    ];
                }
            }

            $text = TextFilter::filter($text, array_keys($filters), array_values($filters));
        }

        /* For level MICRO or NOHTML, start with htmlspecialchars(). */
        $text2 = @htmlspecialchars($text, ENT_COMPAT, $this->params['charset']);

        /* Bad charset input may result in an empty string. Or the charset
         * may not be supported. Convert to UTF-8 for htmlspecialchars() and
         * then convert back. If we STILL don't have any output, the input
         * charset is probably incorrect. Try the popular Western charsets as
         * a last resort. */
        if (!strlen($text2)) {
            $text2 = HordeString::convertCharset(
                @htmlspecialchars(
                    HordeString::convertCharset($text, $this->params['charset'], 'UTF-8'),
                    ENT_COMPAT,
                    'UTF-8'
                ),
                'UTF-8',
                $this->params['charset']
            );

            if (!strlen($text2)) {
                foreach (['windows-1252', 'utf-8'] as $val) {
                    $text2 = HordeString::convertCharset(
                        @htmlspecialchars($text, ENT_COMPAT, $val),
                        $val,
                        $this->params['charset']
                    );

                    if (strlen($text2)) {
                        break;
                    }
                }
            }
        }

        $text = $text2;

        /* Do in-lining of http://xxx.xxx to link, xxx@xxx.xxx to email. */
        if ($this->params['parselevel'] < self::NOHTML) {
            if ($this->params['secretManager']) {
                $text = Linkurls::decode($text, $this->params['secretManager']);
                if ($this->params['parselevel'] < self::MICRO_LINKURL) {
                    $text = Emails::decode($text, $this->params['secretManager']);
                }
            }

            if ($this->params['space2html']) {
                $params = reset($this->params['space2html']);
                $driver = key($this->params['space2html']);
            } else {
                $driver = 'space2html';
                $params = [];
            }

            $text = TextFilter::filter($text, $driver, $params);
        }

        /* Do the newline ---> <br /> substitution. Everybody gets this; if
         * you don't want even that, just use htmlspecialchars(). */
        return nl2br($text);
    }
}
