<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

use Horde\Secret\SecretManager;

/**
 * Finds email addresses in a block of text and turns them into links.
 *
 * Parameters:
 * <pre>
 * class - (string) CSS class of the generated <a> tag.
 *         DEFAULT: ''
 * encode - (boolean) Whether to escape special HTML characters in the URLs
 *          and finally "encode" the complete tag so that it can be decoded
 *          later with the decode() method. This is useful if you want to run
 *          htmlspecialchars() or similar *after* using this filter.
 *          DEFAULT: false
 * secretManager - (SecretManager) SecretManager instance for encoding.
 *                 DEFAULT: null (encoding disabled unless provided)
 * </pre>
 *
 * @author   Tyler Colbert <tyler@colberts.us>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Emails extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'class' => '',
        'encode' => false,
        'secretManager' => null,
    ];

    protected string $regexp;

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        $this->regexp = <<<'EOR'
                    /
                        # Version 1: mailto: links with any valid email characters.
                        # Pattern 1: Outlook parenthesizes in square brackets
                        (\[\s*)?
                        # Pattern 2: mailto: protocol prefix
                        (mailto:\s?)
                        # Pattern 3: email address
                        ([^\s\?"<&]*)
                        # Pattern 4: closing angle brackets?
                        (&gt;)?
                        # Pattern 5 to 7: Optional parameters
                        ((\?)([^\s"<]*[\w+#?\/&=]))?
                        # Pattern 8: Closing Outlook square bracket
                        ((?(1)\s*\]))
                    |
                        # Version 2 Pattern 9 and 10: simple email addresses.
                        (^|\s|&lt;|<|\[)([\w\-+.=]+@[-A-Z0-9.]*[A-Z0-9])
                        # Pattern 11 to 13: Optional parameters
                        ((\?)([^\s"<]*[\w+#?\/&=]))?
                        # Pattern 14: Optional closing bracket
                        (>)?
                    /ix
            EOR;

        return ['regexp_callback' => [
            $this->regexp => [$this, 'regexCallback'],
        ]];
    }

    /**
     * Regular expression callback.
     *
     * @param array $matches  preg_replace_callback() matches. See regex above
     *                        for description of matching data.
     *
     * @return string  Replacement string.
     */
    public function regexCallback(array $matches): string
    {
        $data = $this->buildEmailLink($matches);

        if ($this->params['encode'] && $this->params['secretManager'] instanceof SecretManager) {
            $encrypted = $this->params['secretManager']->encrypt($data);
            $data = "\01\01\01" . base64_encode((string) $encrypted) . "\01\01\01";
        }

        return $matches[1] . $matches[2] . ($matches[9] ?? '')
            . $data
            . $matches[4] . $matches[8] . ($matches[14] ?? '');
    }

    /**
     * Build the email link.
     *
     * @param array $matches  preg_replace_callback() matches.
     *
     * @return string  Replacement string.
     */
    protected function buildEmailLink(array $matches): string
    {
        $class = empty($this->params['class'])
            ? ''
            : ' class="' . $this->params['class'] . '"';
        $email = (!isset($matches[10]) || $matches[10] === '')
            ? $matches[3] . $matches[5]
            : $matches[10] . ($matches[11] ?? '');

        return '<a' . $class . ' href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>';
    }

    /**
     * "Decodes" the text formerly encoded by using the "encode" parameter.
     *
     * @param string $text           An encoded text.
     * @param SecretManager $manager The SecretManager instance.
     *
     * @return string  The decoded text.
     */
    public static function decode(string $text, SecretManager $manager): string
    {
        return preg_replace_callback(
            '/\01\01\01([\w=+\/]*)\01\01\01/',
            fn($hex) => $manager->decrypt(base64_decode($hex[1])),
            $text
        );
    }
}
