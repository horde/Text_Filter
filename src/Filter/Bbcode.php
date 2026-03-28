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
 * Finds bbcode-style markup in a block of text and turns it into HTML.
 *
 * Parameters:
 * <pre>
 * entities - (boolean) Before replacing bbcode with HTML tags, replace HTML
 *            entities?
 *            DEFAULT: false
 * </pre>
 *
 * @author   Carlos Pedrinaci Godoy <cpedrinaci@yahoo.es>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 *
 * TODO: Complete PSR-4 port from lib/Horde/Text/Filter/Bbcode.php
 */
class Bbcode extends Base
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
        // TODO: Port full bbcode patterns from legacy class
        return [];
    }
}
