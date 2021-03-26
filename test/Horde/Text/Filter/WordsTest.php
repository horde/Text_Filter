<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */
namespace Horde\Text\Filter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the words filter.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class WordsTest extends TestCase
{
    private $words;

    public function setUp(): void
    {
        $this->words = array('foo', 'bar');
    }

    public function testBasicFiltering()
    {
        $line = 'foo baz bar';

        $res = Horde_Text_Filter::filter($line, 'words', array(
            'words' => $this->words
        ));

        $this->assertEquals(
            '***** baz *****',
            $res
        );
    }

    public function testDefaultReplacement()
    {
        $line = 'foo baz';

        $res = Horde_Text_Filter::filter($line, 'words', array(
            'replacement' => null,
            'words' => $this->words
        ));

        $this->assertEquals(
            'f** baz',
            $res
        );
    }

}
