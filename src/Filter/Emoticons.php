<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

/**
 * Finds emoticon strings in a block of text and does a transformation on them.
 *
 * By default, this filter does not do any transformation to the emoticon.
 *
 * Parameters:
 * <pre>
 * entities - (boolean) Use HTML entity versions of the patterns?
 *            DEFAULT: false
 * </pre>
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 *
 * TODO: Complete PSR-4 port from lib/Horde/Text/Filter/Emoticons.php
 */
class Emoticons extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'entities' => false,
    ];

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns(): array
    {
        // TODO: Port full emoticon patterns from legacy class
        return [];
    }
}
