<?php

/**
 * Copyright 2015-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

namespace Horde\Text\Filter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde_Text_Filter;

/**
 * Tests for the simple markup filter.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 * @coversNothing
 */
class SimplemarkupTest extends TestCase
{
    #[DataProvider('markupExamples')]
    public function testSimplemarkup($markup, $output, $html)
    {
        $this->assertEquals(
            $output,
            Horde_Text_Filter::filter($markup, 'simplemarkup', ['html' => $html])
        );
    }

    public static function markupExamples()
    {
        return [
            // Simple examples.
            [
                'some *bold* text',
                'some <strong>*bold*</strong> text',
                false,
            ],
            [
                'some _underlined_ text',
                'some <u>_underlined_</u> text',
                false,
            ],
            [
                'some /italic/ text',
                'some <em>/italic/</em> text',
                false,
            ],

            // Edge cases.
            [
                '*bold* at start',
                '<strong>*bold*</strong> at start',
                false,
            ],
            [
                'at end *bold*',
                'at end <strong>*bold*</strong>',
                false,
            ],
            [
                'full stop *bold*.',
                'full stop <strong>*bold*</strong>.',
                false,
            ],
            [
                'some&nbsp;*bold*&nbsp;text',
                'some&nbsp;<strong>*bold*</strong>&nbsp;text',
                true,
            ],
            [
                'some<br>*bold*<br />text more<br />*bold*<br>text',
                'some<br><strong>*bold*</strong><br />text more<br /><strong>*bold*</strong><br>text',
                true,
            ],

            // Whole phrase matching.
            [
                '*some bold text*',
                '<strong>*some bold text*</strong>',
                false,
            ],
            [
                ' *some bold text* ',
                ' <strong>*some bold text*</strong> ',
                false,
            ],
            [
                '&nbsp;*some bold&nbsp;text*&nbsp;',
                '&nbsp;<strong>*some bold&nbsp;text*</strong>&nbsp;',
                true,
            ],
            [
                '<br>*some bold text*<br />',
                '<br><strong>*some bold text*</strong><br />',
                true,
            ],

            // No matching.
            [
                'some *bold**bold* text',
                'some *bold**bold* text',
                false,
            ],
            [
                'some *bold*bold* text',
                'some *bold*bold* text',
                false,
            ],
            [
                'some bold*bold text',
                'some bold*bold text',
                false,
            ],

            // More edge cases.
            [
                "* some bullet point\n* ...\n",
                "* some bullet point\n* ...\n",
                false,
            ],
            [
                "* some bullet point<br>* ...<br>",
                "* some bullet point<br>* ...<br>",
                true,
            ],
            [
                'some *bold* *text*.',
                'some <strong>*bold*</strong> <strong>*text*</strong>.',
                false,
            ],
        ];
    }
}
