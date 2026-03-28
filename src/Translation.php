<?php

declare(strict_types=1);

/**
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Text\Filter;

use Horde\Translation\Autodetect;

/**
 * Translation wrapper class for Horde\Text\Filter.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Text_Filter
 */
class Translation extends Autodetect
{
    /**
     * The translation domain
     */
    protected static string $domain = 'Horde_Text_Filter';

    /**
     * The absolute PEAR path to the translations for the default gettext handler.
     */
    protected static string $pearDirectory = '@data_dir@';
}
