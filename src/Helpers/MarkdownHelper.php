<?php

namespace Amarenkov\MutableContentDaisyUi\Helpers;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Markdown of text fields: safe HTML for display and plain text for short previews.
 */
class MarkdownHelper
{
    // const
    protected const OPTIONS = [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
    ];

    // static
    public static function toHtml(?string $markdown): HtmlString
    {
        return new HtmlString($markdown === null || $markdown === '' ? '' : Str::markdown($markdown, self::OPTIONS));
    }

    public static function toText(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags(Str::markdown($markdown, self::OPTIONS)), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
