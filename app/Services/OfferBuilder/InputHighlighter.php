<?php

namespace App\Services\OfferBuilder;

use App\Models\OfferStrategy;

/**
 * Gives each value the user entered in the offer form its own colour and marks
 * where those values appear in the offer text, so the user sees what they added
 * and how it is used. Output is HTML-escaped; only <mark> tags are added.
 */
class InputHighlighter
{
    /** Tailwind classes, one per input (cycled). */
    public const COLORS = [
        'bg-orange-100 text-orange-900 ring-orange-200',
        'bg-sky-100 text-sky-900 ring-sky-200',
        'bg-emerald-100 text-emerald-900 ring-emerald-200',
        'bg-violet-100 text-violet-900 ring-violet-200',
        'bg-rose-100 text-rose-900 ring-rose-200',
        'bg-amber-100 text-amber-900 ring-amber-200',
        'bg-teal-100 text-teal-900 ring-teal-200',
        'bg-fuchsia-100 text-fuchsia-900 ring-fuchsia-200',
        'bg-lime-100 text-lime-900 ring-lime-200',
        'bg-indigo-100 text-indigo-900 ring-indigo-200',
    ];

    /** Inputs that are only context, never shown as chips. */
    private const SKIP = ['language', 'brand_voice', 'website', 'portfolio', 'business_description'];

    /**
     * @return list<array{key: string, label: string, value: string, class: string}>
     */
    public static function chips(?OfferStrategy $strategy, array $inputs): array
    {
        $labels = [];
        $fields = $strategy ? app(OfferPromptBuilderService::class)->fields($strategy) : OfferPromptBuilderService::GLOBAL_FIELDS;
        foreach ($fields as $f) {
            $labels[$f['key']] = $f['label'];
        }

        $chips = [];
        foreach ($inputs as $key => $value) {
            $value = trim((string) $value);
            if ($value === '' || in_array($key, self::SKIP, true) || !isset($labels[$key])) {
                continue;
            }
            $chips[] = ['key' => $key, 'label' => $labels[$key], 'value' => $value, 'class' => self::COLORS[count($chips) % count(self::COLORS)]];
        }

        return $chips;
    }

    /** Escaped text with every chip value wrapped in its coloured <mark>. */
    public static function html(?string $text, array $chips): string
    {
        $escaped = e((string) $text);
        if ($escaped === '' || $chips === []) {
            return $escaped;
        }

        // Longest values first so "Clinic website design" wins over "Clinic"
        usort($chips, fn ($a, $b) => mb_strlen($b['value']) <=> mb_strlen($a['value']));
        $marks = [];
        foreach ($chips as $chip) {
            if (mb_strlen($chip['value']) < 3) {
                continue;
            }
            $pattern = '/' . preg_quote(e($chip['value']), '/') . '/iu';
            $escaped = preg_replace_callback($pattern, function ($m) use (&$marks, $chip) {
                $marks[] = '<mark class="rounded px-0.5 ring-1 ' . $chip['class'] . '" title="' . e($chip['label']) . '">' . $m[0] . '</mark>';

                return "\u{E000}" . (count($marks) - 1) . "\u{E001}";
            }, $escaped) ?? $escaped;
        }

        return preg_replace_callback("/\u{E000}(\\d+)\u{E001}/u", fn ($m) => $marks[(int) $m[1]], $escaped) ?? $escaped;
    }
}
