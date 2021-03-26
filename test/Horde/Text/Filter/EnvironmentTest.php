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
use \Horde_Text_Filter;

class EnvironmentTest extends TestCase
{
    public function setUp(): void
    {
        putenv('COMMENT=comment');
        putenv('FOO=bar');
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testEnvironment($input, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Text_Filter::filter($input, 'environment')
        );
    }

    public function environmentProvider()
    {
        return array(
            array('Simple line', 'Simple line'),
            array('Inline %FOO% variable', 'Inline bar variable'),
            array('%FOO% at start', 'bar at start'),
            array('at end %FOO%', 'at end bar'),
            array('# %COMMENT% line', ''),
            array('Variable %FOO% with # comment %COMMENT%', 'Variable bar with '),
            array('Simple line', 'Simple line')
        );
    }

}
