<?php
/**
 * Horde_Text_Filter_Text2html tests.
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
use \Horde_Text_Filter_Text2html;

class Text2htmlTest extends TestCase
{
    /**
     * @dataProvider text2htmlProvider
     */
    public function testText2html($input, $level, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Text_Filter::filter($input, 'text2html', array(
                'parselevel' => $level,
                'secretKey' => "mGmEXue4Az0YurdMK6p3alB"
                )
            )
        );
    }

    public function text2htmlProvider()
    {
        $tests = array(
            'http://www.horde.org/foo/',
            'https://jan:secret@www.horde.org/foo/',
            'mailto:jan@example.com',
            'svn+ssh://jan@svn.example.com/path/',
            '<tag>foo</tag>',
            '<http://css.maxdesign.com.au/listamatic/>',
            'http://www.horde.org/?foo=bar&baz=qux',
            'http://www.<alert>.horde.org/',
            'http://www.&#x32;.horde.org/'
        );

        $levels = array(
            Horde_Text_Filter_Text2html::PASSTHRU => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '<tag>foo</tag>',
                '<http://css.maxdesign.com.au/listamatic/>',
                'http://www.horde.org/?foo=bar&baz=qux',
                'http://www.<alert>.horde.org/',
                'http://www.&#x32;.horde.org/',
            ),
            Horde_Text_Filter_Text2html::SYNTAX => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:<a href="mailto:jan@example.com">jan@example.com</a>',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::MICRO => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:<a href="mailto:jan@example.com">jan@example.com</a>',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::MICRO_LINKURL => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:jan@example.com',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::NOHTML => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;http://css.maxdesign.com.au/listamatic/&gt;',
                'http://www.horde.org/?foo=bar&amp;baz=qux',
                'http://www.&lt;alert&gt;.horde.org/',
                'http://www.&amp;#x32;.horde.org/'
            ),
            Horde_Text_Filter_Text2html::NOHTML_NOBREAK => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;http://css.maxdesign.com.au/listamatic/&gt;',
                'http://www.horde.org/?foo=bar&amp;baz=qux',
                'http://www.&lt;alert&gt;.horde.org/',
                'http://www.&amp;#x32;.horde.org/'
            )
        );

        $out = array();
        foreach ($levels as $level => $results) {
            foreach ($tests as $key => $val) {
                $out[] = array($val, $level, $results[$key]);
            }
        }

        return $out;
    }

    public function testBug12253()
    {
        // ISO-8859-2 encoded data.
        $text = base64_decode('a/ZubmVu');

        $filter = Horde_Text_Filter::filter($text, 'text2html', array(
            'charset' => 'iso-8859-2',
            'parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL
        ));

        $this->assertGreaterThan(0, strlen($filter));
    }

}
