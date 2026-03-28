<?php

/**
 * Horde_Text_Filter_Environment tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
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
 * @coversNothing
 */
class EnvironmentTest extends TestCase
{
    public function setUp(): void
    {
        putenv('COMMENT=comment');
        putenv('FOO=bar');
    }

    #[DataProvider('environmentProvider')]
    public function testEnvironment($input, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Text_Filter::filter($input, 'environment')
        );
    }

    public static function environmentProvider()
    {
        return [
            ['Simple line', 'Simple line'],
            ['Inline %FOO% variable', 'Inline bar variable'],
            ['%FOO% at start', 'bar at start'],
            ['at end %FOO%', 'at end bar'],
            ['# %COMMENT% line', ''],
            ['Variable %FOO% with # comment %COMMENT%', 'Variable bar with '],
            ['Simple line', 'Simple line'],
        ];
    }

}
