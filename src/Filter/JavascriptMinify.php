<?php

declare(strict_types=1);

/**
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter\Filter;

/**
 * This filter cleans up javascript output by running it through an
 * optimizer/compressor.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @deprecated Use Horde_JavascriptMinify package instead.
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 *
 * TODO: Complete PSR-4 port from lib/Horde/Text/Filter/JavascriptMinify.php
 */
class JavascriptMinify extends Base
{
    /**
     * Filter parameters.
     */
    protected array $params = [
        'closure' => null,
        'java' => null,
        'yui' => null,
    ];

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess(string $text): string
    {
        // TODO: Port full implementation from legacy class
        // For now, return text unchanged
        return $text;
    }
}
