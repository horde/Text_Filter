<?php

/**
 * Horde_Text_Filter_Linkurls tests.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
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
class LinkurlsTest extends TestCase
{
    #[DataProvider('urlProvider')]
    public function testLinkurls($testText, $expected)
    {
        // need to update regexp per http://daringfireball.net/2010/07/improved_regex_for_matching_urls to fully pass
        $actual = Horde_Text_Filter::filter($testText, 'linkurls', ['target' => null]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test data from
     * http://daringfireball.net/misc/2010/07/url-matching-regex-test-data.text
     */
    public static function urlProvider()
    {
        return [
            /* No match */
            ['6:00p', '6:00p'],
            ['filename.txt', 'filename.txt'],

            /* Not matchng mailto: intentionally */
            ['What about <mailto:gruber@daringfireball.net?subject=TEST> (including brokets).', 'What about <mailto:gruber@daringfireball.net?subject=TEST> (including brokets).'],
            ['mailto:name@example.com', 'mailto:name@example.com'],

            /* Matched correctly */
            ['http://foo.com/blah_blah', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>'],
            ['http://foo.com/blah_blah/', '<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>'],
            ['https://foo.com/blah_blah/', '<a href="https://foo.com/blah_blah/">https://foo.com/blah_blah/</a>'],
            ['://foo.com/blah_blah/', '<a href="://foo.com/blah_blah/">://foo.com/blah_blah/</a>'],
            ['(Something like http://foo.com/blah_blah)', '(Something like <a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>)'],
            ['http://foo.com/blah_blah_(wikipedia)', '<a href="http://foo.com/blah_blah_(wikipedia)">http://foo.com/blah_blah_(wikipedia)</a>'],
            ['http://foo.com/more_(than)_one_(parens)', '<a href="http://foo.com/more_(than)_one_(parens)">http://foo.com/more_(than)_one_(parens)</a>'],
            ['(Something like http://foo.com/blah_blah_(wikipedia))', '(Something like <a href="http://foo.com/blah_blah_(wikipedia)">http://foo.com/blah_blah_(wikipedia)</a>)'],
            ['http://foo.com/blah_(wikipedia)#cite-1', '<a href="http://foo.com/blah_(wikipedia)#cite-1">http://foo.com/blah_(wikipedia)#cite-1</a>'],
            ['http://foo.com/blah_(wikipedia)_blah#cite-1', '<a href="http://foo.com/blah_(wikipedia)_blah#cite-1">http://foo.com/blah_(wikipedia)_blah#cite-1</a>'],
            ['http://foo.com/unicode_(✪)_in_parens', '<a href="http://foo.com/unicode_(✪)_in_parens">http://foo.com/unicode_(✪)_in_parens</a>'],
            ['http://foo.com/(something)?after=parens', '<a href="http://foo.com/(something)?after=parens">http://foo.com/(something)?after=parens</a>'],
            ['http://foo.com/blah_blah.', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>.'],
            ['http://foo.com/blah_blah/.', '<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>.'],
            ['<http://foo.com/blah_blah>', '<<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>>'],
            ['<http://foo.com/blah_blah/>', '<<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>>'],
            ['http://foo.com/blah_blah,', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>,'],
            ['http://www.extinguishedscholar.com/wpglob/?p=364.', '<a href="http://www.extinguishedscholar.com/wpglob/?p=364">http://www.extinguishedscholar.com/wpglob/?p=364</a>.'],
            ['http://✪df.ws/1234', '<a href="http://✪df.ws/1234">http://✪df.ws/1234</a>'],
            ['rdar://1234', '<a href="rdar://1234">rdar://1234</a>'],
            ['rdar:/1234', '<a href="rdar:/1234">rdar:/1234</a>'],
            ['x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E', '<a href="x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E">x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E</a>'],
            ['message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e', '<a href="message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e">message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e</a>'],
            ['http://➡.ws/䨹', '<a href="http://➡.ws/䨹">http://➡.ws/䨹</a>'],
            ['www.c.ws/䨹', '<a href="http://www.c.ws/䨹">www.c.ws/䨹</a>'],
            ['<tag>http://example.com</tag>', '<tag><a href="http://example.com">http://example.com</a></tag>'],
            ['Just a www.example.com link.', 'Just a <a href="http://www.example.com">www.example.com</a> link.'],
            ['http://example.com/something?with,commas,in,url, but not at end', '<a href="http://example.com/something?with,commas,in,url">http://example.com/something?with,commas,in,url</a>, but not at end'],
            ['bit.ly/foo', '<a href="http://bit.ly/foo">bit.ly/foo</a>'],
            ['“is.gd/foo/”', '“<a href="http://is.gd/foo/">is.gd/foo/</a>”'],
            ['WWW.EXAMPLE.COM', '<a href="http://WWW.EXAMPLE.COM">WWW.EXAMPLE.COM</a>'],
            ['http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752', '<a href="http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752">http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752</a>'],
            ['http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))', '<a href="http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))">http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))</a>'],
            ['http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))', '<a href="http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))">http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))</a>'],

            /* Known failures */
            // array('http://example.com/quotes-are-“part”', 'http://example.com/quotes-are-“part”'),
            // array('✪df.ws/1234', '✪df.ws/1234'),
            ['example.com', 'example.com'],
            ['example.com/', 'example.com/'],
        ];
    }

    public function testBug10516()
    {
        $testText = '[http://example.com](http://example2.com)';

        $actual = Horde_Text_Filter::filter($testText, 'linkurls', [
            'target' => null,
        ]);

        $this->assertEquals(
            '[<a href="http://example.com">http://example.com</a>](<a href="http://example2.com">http://example2.com</a>)',
            $actual
        );
    }

    public function testUrlWithManyQuestionMarks()
    {
        $this->assertEquals(
            '<a href="http://www.example.com">http://www.example.com</a>?????????????????????????????????????????????????????????????????',
            Horde_Text_Filter::filter(
                'http://www.example.com?????????????????????????????????????????????????????????????????',
                'linkurls',
                ['target' => null]
            )
        );
    }

    public function testBug11116()
    {
        $text = file_get_contents(__DIR__ . '/fixtures/bug_11116.txt');

        $this->assertNotNull(
            Horde_Text_Filter::filter($text, 'linkurls')
        );
    }

    public function testBug12152()
    {
        $text = 'http://imslp.org/wiki/Symphony_No.5,_D.485_(Schubert,_Franz)';

        $old_ini = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', 1000);

        $this->assertEquals(
            $text,
            Horde_Text_Filter::filter($text, 'linkurls')
        );

        ini_set('pcre.backtrack_limit', $old_ini);
    }

}
