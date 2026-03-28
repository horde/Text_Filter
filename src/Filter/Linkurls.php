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
use Horde_Idna;
use Horde_Idna_Exception;
use InvalidArgumentException;

/**
 * Turns all URLs in the text into hyperlinks.
 *
 * The regex used is adapted from John Gruber's:
 * http://daringfireball.net/2010/07/improved_regex_for_matching_urls
 *
 * Changes:
 *   - Require at least one slash after the protocol. Horde's other filters
 *     don't expect us to match mailto: as part of these filters, so don't.
 *   - Limit the URL protocol to 20 characters to avoid PCRE problems.
 *   - Allow "+" characters in URL protocols (like svn+ssh://).
 *
 * Parameters:
 *   - callback: (string) A callback function that the URL is passed through
 *               before being set as the href attribute.  Must be a string
 *               with the function name, the function must take the original
 *               URL as the first and only parameter.
 *               DEFAULT: No callback
 *   - class: (string) The CSS class of the generated links.
 *            DEFAULT: none
 *   - encode: (boolean)  Whether to escape special HTML characters in the
 *             URLs and finally "encode" the complete tag so that it can be
 *             decoded later with the decode() method. This is useful if you
 *             want to run htmlspecialchars() or similar *after* using this
 *             filter.
 *             DEFAULT: false
 *   - nofollow: (boolean) Whether to set the 'rel="nofollow"' attribute on
 *               links.
 *               DEFAULT: false
 *   - target: (string) The link target.
 *             DEFAULT: '_blank'
 *   - secretManager: (SecretManager) SecretManager instance for encoding.
 *                    DEFAULT: null (encoding disabled unless provided)
 *
 * @author   Tyler Colbert <tyler@colberts.us>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Linkurls extends Base
{
    /**
     * Link-finding regex
     */
    public static string $regex = '';

    /**
     * Filter parameters.
     */
    protected array $params = [
        'callback' => null,
        'class' => '',
        'encode' => false,
        'nofollow' => false,
        'target' => '_blank',
        'secretManager' => null,
    ];

    /**
     * Return the regex used to search for links.
     *
     * @return string  The regex string.
     */
    public static function getRegex(): string
    {
        if (!self::$regex) {
            self::initializeRegex();
        }

        return self::$regex;
    }

    /**
     * Initialize the regex for this instance.
     */
    public static function initializeRegex(): void
    {
        self::$regex = <<<'END_OF_REGEX'
            (?xi)
            (?:\b|^)
            (  # Capture 1: entire matched URL
              (
               (?:[a-z][\w\-+]{0,19})?:/{1,3}  # URL protocol and colon followed by 1-3
                                              # slashes, or just colon and slashes (://)
                |                             #  - or -
                (?<!\.)www\d{0,3}\.           # "www.", "www1.", "www2." … "www999."
                                              # without a leading period
                |                             #  - or -
                [a-z0-9.\-]+\.[a-z]{2,4}/    # looks like domain name followed by a slash
              )
              (?:                           # One or more:
                [^\s()<>\[\]]+                         # Run of non-space, non-()<>
                (?<![\s`!()\[\]{};:\'".,<>?«»""'']{2}) # that is not followed by two or more
                                                       # punct chars that indicate end-of-url
                |                                      #  - or -
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\)     # balanced parens, up to 2 levels
              )+
              (?:                           # End with:
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
                |                                   #  - or -
                [^\s`!()\[\]{};:\'".,<>?«»""'']     # not a space or one of these punct
                                                    # chars
              )
            )
            END_OF_REGEX;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        return [
            'regexp_callback' => ['@' . self::getRegex() . '@' => [$this, 'callback']],
        ];
    }

    /**
     * Callback for URL replacement.
     *
     * @param array $match  The regex match array.
     *
     * @return string  The replacement text.
     */
    public function callback(array $match): string
    {
        $href = $orig_href = $match[0];
        if (!str_contains($match[2], ':')) {
            $href = 'http://' . $href;
        }

        if ($this->params['callback']) {
            $href = call_user_func($this->params['callback'], $href);
        }

        $href = htmlspecialchars($href);

        $class = $this->params['class'];
        if (!empty($class)) {
            $class = ' class="' . $class . '"';
        }

        $target = $this->params['target'];
        if (!empty($target)) {
            $target = ' target="' . $target . '"';
        }

        $decoded = $orig_href;
        try {
            $host = $this->parseUrl($orig_href, PHP_URL_HOST);
            if (is_string($host) && strlen($host)) {
                $decoded = substr_replace(
                    $orig_href,
                    Horde_Idna::decode($host),
                    strpos($orig_href, $host),
                    strlen($host)
                );
            }
        } catch (Horde_Idna_Exception|InvalidArgumentException) {
        }

        $replacement = '<a href="' . $href . '"'
            . ($this->params['nofollow'] ? ' rel="nofollow"' : '')
            . $target . $class
            . '>' . htmlspecialchars($decoded) . '</a>';

        if (!empty($this->params['noprefetch'])) {
            $replacement = '<meta http-equiv="x-dns-prefetch-control" value="off" />'
                . $replacement
                . '<meta http-equiv="x-dns-prefetch-control" value="on" />';
        }

        if ($this->params['encode'] && $this->params['secretManager'] instanceof SecretManager) {
            $encrypted = $this->params['secretManager']->encrypt($replacement);
            $replacement = chr(0) . chr(0) . chr(0) . base64_encode((string) $encrypted) . chr(0) . chr(0) . chr(0);
        }

        return $replacement;
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
            '/\00\00\00([\w=+\/]*)\00\00\00/',
            function ($hex) use ($manager) {
                return $manager->decrypt(base64_decode($hex[1]));
            },
            $text
        );
    }

    /**
     * Handle multi-byte data since parse_url is not multibyte safe on all
     * systems. Adapted from php.net/parse_url comments.
     *
     * See https://bugs.php.net/bug.php?id=52923 for description of
     * parse_url issues.
     *
     * @param string $url  The url to parse.
     * @param int $component  The specific URL component to retrieve.
     *
     * @return mixed  The parsed url component.
     * @throws InvalidArgumentException
     */
    protected function parseUrl(string $url, int $component = -1): mixed
    {
        $enc_url = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            fn($matches) => urlencode($matches[0]),
            $url
        );
        $parts = @parse_url($enc_url, $component);
        if ($parts === false) {
            throw new InvalidArgumentException('Malformed URL: ' . $url);
        }

        if ($component === -1 && is_array($parts)) {
            foreach ($parts as $name => $value) {
                $parts[$name] = urldecode($value);
            }
            return $parts;
        }

        return is_string($parts) ? urldecode($parts) : $parts;
    }
}
