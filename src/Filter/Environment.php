<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

/**
 * Replaces occurences of %VAR% with VAR, if VAR exists in the webserver's
 * environment.  Ignores all text after a '#' character (shell-style
 * comments).
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Environment extends Base
{
    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        $regexp = [
            '/^#.*$\n/m' => '',
            '/^([^#]*)#.*$/m' => '$1',
        ];

        $regexp_callback = [
            '/%([A-Za-z_]+)%/' => [$this, 'regexCallback'],
        ];

        return [
            'regexp' => $regexp,
            'regexp_callback' => $regexp_callback,
        ];
    }

    /**
     * Preg callback.
     *
     * @param array $matches  preg_replace_callback() matches.
     *
     * @return string|false  The replacement string or false.
     */
    public function regexCallback(array $matches): string|false
    {
        return getenv($matches[1]);
    }
}
