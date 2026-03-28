# Upgrading Text_Filter

## Version 3.0.0

### Breaking Changes

#### JavascriptMinify Filter Removed

The deprecated JavascriptMinify filter has been removed. It was deprecated since version 2.3.0 (2014-05-02).

**Migration:** Use the `horde/javascriptminify` package instead:

```bash
composer require horde/javascriptminify
```

**Before (no longer works):**
```php
$minified = Horde_Text_Filter::filter($javascript, 'javascriptminify', [
    'java' => '/usr/bin/java',
    'closure' => '/path/to/compiler.jar',
]);
```

**After:**
```php
use Horde\JavascriptMinify\Closure;

$minifier = new Closure('/path/to/compiler.jar');
$minified = $minifier->minify($javascript);
```

### PHP Version

Text_Filter 3.0 requires PHP 7.4 or higher.

### New Features

#### PSR-4 Implementation

Version 3.0 introduces a modern PSR-4 implementation in the `src/` directory alongside the existing PSR-0 implementation in `lib/`.

**PSR-0 (existing, fully compatible):**
```php
use Horde_Text_Filter;

$result = Horde_Text_Filter::filter($text, 'linkurls', $params);
```

**PSR-4 (new, modern):**
```php
use Horde\Text\Filter\TextFilter;

$result = TextFilter::filter($text, 'linkurls', $params);
```

**Both APIs work simultaneously.** Existing code requires no immediate changes but is encouraged to upgrade.

#### API Improvements

**Xss Filter:** The confusing `return_dom` parameter has been removed from the PSR-4 implementation. The filter now always returns a string for consistent behavior in filter pipelines.

**Before (PSR-0, still works):**
```php
// Could return string OR Domhtml object - breaks filter chaining
$xss = new Horde_Text_Filter_Xss(['return_dom' => true]);
$dom = $xss->filter($html);  // Returns Domhtml object
```

**After (PSR-4, recommended):**
```php
// Always returns string - works in filter pipelines
use Horde\Text\Filter\TextFilter;
$clean = TextFilter::filter($html, 'xss');

// Need DOM manipulation? Use Domhtml directly
use Horde\Util\Domhtml;
$dom = new Domhtml($html);
// ... manipulate DOM ...
$clean = $dom->returnBody();
```

### Migration Guide

#### Option 1: Migrate to PSR-4 (Recommended for New Code)

Update namespace imports and use modern API:

```php
use Horde\Text\Filter\TextFilter;

// Same method signatures, modern implementation
$result = TextFilter::filter($text, 'linkurls');
```

#### Option 2: Gradual Migration

Migrate incrementally - both APIs work in the same application:

```php
// Old code continues working
$result1 = Horde_Text_Filter::filter($text1, 'emails');

// New code uses PSR-4
use Horde\Text\Filter\TextFilter;
$result2 = TextFilter::filter($text2, 'linkurls');
```

### Available Filters

Version 3.0 includes 18 filters (19 in v2.x, minus removed JavascriptMinify):

- bbcode
- cleanascii
- cleanhtml
- dimsignature
- emails
- emoticons
- environment
- highlightquotes
- html2text
- linkurls
- msoffice
- simplemarkup
- space2html
- tabs2spaces
- text2html
- words
- xss

All filters available in both PSR-0 (`lib/`) and PSR-4 (`src/`) implementations.

### Dependency Changes

#### Removed Dependencies

- `horde/text_filter_jsmin` - No longer needed (JavascriptMinify filter removed)

#### Updated Dependencies (PSR-4 only)

The PSR-4 implementation uses modern Horde 6 packages:

- `horde/util` → Uses `Horde\Util\HordeString`, `Horde\Util\Util`, `Horde\Util\Domhtml`
- `horde/secret` → Uses `Horde\Secret\SecretManager` (instead of legacy `Horde_Secret`)

**Note:** PSR-0 implementation remains unchanged for maximum backward compatibility.

### Testing

All 266 tests pass in version 3.0 with zero regressions.

```bash
vendor/bin/phpunit
# Tests: 266, Assertions: 252, Skipped: 23
```

Skipped tests are for Html2text filter (requires tidy extension).
