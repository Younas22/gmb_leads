<?php

namespace App\Services\Web;

use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

/**
 * Extracts verifiable signals from one HTML page. Scripts, styles and other
 * non-visible content are removed first; the text excerpt is plain text only.
 * Nothing here executes or follows anything from the page.
 */
class WebsiteSignalExtractor
{
    private const STRIP_TAGS = ['script', 'style', 'noscript', 'template', 'iframe', 'svg', 'object', 'embed', 'canvas', 'head'];

    private const CTA_PATTERN = '/\b(book|booking|appointment|schedule|call (us|now|today)|contact us|get (a )?(free )?(quote|estimate|started)|request (a )?(quote|call|demo)|order (now|online)|buy now|shop now|reserve|enquire|inquire|sign up|subscribe|whatsapp)\b/i';

    private const SOCIAL = [
        'facebook' => ['facebook.com', 'fb.com'],
        'instagram' => ['instagram.com'],
        'linkedin' => ['linkedin.com'],
        'x' => ['twitter.com', 'x.com'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'tiktok' => ['tiktok.com'],
        'pinterest' => ['pinterest.com'],
    ];

    /** @return array<string, mixed> */
    public function extract(string $html, string $pageUrl): array
    {
        $html = mb_scrub($html, 'UTF-8');
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);

        // Head metadata (read before stripping <head>)
        $title = $this->clean($xpath->evaluate('string(//title)'));
        $metaDescription = $this->metaContent($xpath, 'description');
        $hasViewport = $this->metaContent($xpath, 'viewport') !== null;
        $ogTitle = $this->metaProperty($xpath, 'og:title');
        $lang = $this->clean($xpath->evaluate('string(/html/@lang)')) ?: null;
        $structuredTypes = $this->structuredDataTypes($xpath);

        // Links (anchors live in the body, so read them before stripping too)
        $host = strtolower((string) parse_url($pageUrl, PHP_URL_HOST));
        $internal = [];
        $social = [];
        $phones = [];
        $emails = [];
        $ctas = [];
        $bookingLinks = 0;

        foreach ($xpath->query('//a[@href]') as $a) {
            /** @var DOMElement $a */
            $href = trim($a->getAttribute('href'));
            $text = $this->clean($a->textContent);

            if (stripos($href, 'tel:') === 0) {
                $phones[] = $this->clean(rawurldecode(substr($href, 4)));
                continue;
            }
            if (stripos($href, 'mailto:') === 0) {
                $emails[] = strtolower($this->clean(explode('?', rawurldecode(substr($href, 7)))[0]));
                continue;
            }
            if ($href === '' || $href[0] === '#' || preg_match('/^(javascript|data|vbscript):/i', $href)) {
                continue;
            }

            try {
                $absolute = (string) UriResolver::resolve(new Uri($pageUrl), new Uri($href));
            } catch (\Throwable) {
                continue;
            }
            $linkHost = strtolower((string) parse_url($absolute, PHP_URL_HOST));

            foreach (self::SOCIAL as $platform => $domains) {
                foreach ($domains as $domain) {
                    if ($linkHost === $domain || str_ends_with($linkHost, '.' . $domain)) {
                        $social[$platform] ??= strtok($absolute, '?#');
                    }
                }
            }

            if (preg_match('/(calendly\.com|acuityscheduling|setmore|booksy|fresha|opentable|simplybook|square\.site\/book)/i', $absolute)) {
                $bookingLinks++;
            }

            if ($this->sameSite($linkHost, $host) && preg_match('#^https?://#i', $absolute)) {
                $internal[] = ['url' => strtok($absolute, '#'), 'text' => mb_substr($text, 0, 80)];
            }

            if ($text !== '' && mb_strlen($text) <= 60 && preg_match(self::CTA_PATTERN, $text)) {
                $ctas[] = $text;
            }
        }

        foreach ($xpath->query('//button|//input[@type="submit"]') as $button) {
            /** @var DOMElement $button */
            $text = $this->clean($button->textContent ?: $button->getAttribute('value'));
            if ($text !== '' && mb_strlen($text) <= 60 && preg_match(self::CTA_PATTERN, $text)) {
                $ctas[] = $text;
            }
        }

        // Forms
        $forms = 0;
        $contactForms = 0;
        foreach ($xpath->query('//form') as $form) {
            $forms++;
            $fields = $xpath->query('.//input[@type="email" or @type="tel" or contains(@name,"email") or contains(@name,"phone")]|.//textarea', $form);
            if ($fields->length > 0) {
                $contactForms++;
            }
        }

        // Headings
        $h1 = $this->texts($xpath, '//h1', 5);
        $h2 = $this->texts($xpath, '//h2', 10);

        // Strip non-visible elements, then take plain text
        foreach (self::STRIP_TAGS as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        $text = $this->clean($bodyNode ? $bodyNode->textContent : $dom->textContent);

        // Contact details visible in text
        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            foreach ($m[0] as $email) {
                if (!preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $email)) {
                    $emails[] = strtolower($email);
                }
            }
        }
        if (preg_match_all('/(?:\+?\d[\d\s().\-]{7,}\d)/', $text, $m)) {
            foreach ($m[0] as $phone) {
                if (strlen(preg_replace('/\D/', '', $phone)) >= 8) {
                    $phones[] = trim($phone);
                }
            }
        }

        $internal = $this->uniqueBy($internal, 'url', 150);

        return [
            'page_url' => $pageUrl,
            'title' => $title !== '' ? mb_substr($title, 0, 255) : null,
            'meta_description' => $metaDescription !== null ? mb_substr($metaDescription, 0, 500) : null,
            'og_title' => $ogTitle,
            'has_viewport_meta' => $hasViewport,
            'lang' => $lang,
            'h1' => $h1,
            'h2' => $h2,
            'forms' => $forms,
            'contact_forms' => $contactForms,
            'ctas' => array_values(array_slice(array_unique($ctas), 0, 10)),
            'phones' => array_values(array_slice(array_unique(array_filter($phones)), 0, 5)),
            'emails' => array_values(array_slice(array_unique(array_filter($emails)), 0, 5)),
            'social_links' => $social,
            'booking_links' => $bookingLinks,
            'structured_data_types' => $structuredTypes,
            'contact_page_url' => $this->findPage($internal, '/contact|get-in-touch|reach-us|enquir|inquir/i'),
            'about_page_url' => $this->findPage($internal, '/about|who-we-are|our-story/i'),
            'services_page_url' => $this->findPage($internal, '/services?|what-we-do|treatments|menu|pricing|products/i'),
            'internal_link_count' => count($internal),
            'word_count' => $text === '' ? 0 : count(preg_split('/\s+/u', $text)),
            'text_excerpt' => mb_substr($text, 0, (int) config('ai.analysis.max_website_chars', 6000)),
        ];
    }

    private function metaContent(DOMXPath $xpath, string $name): ?string
    {
        foreach ($xpath->query('//meta[@name]') as $meta) {
            /** @var DOMElement $meta */
            if (strtolower($meta->getAttribute('name')) === $name) {
                $value = $this->clean($meta->getAttribute('content'));
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function metaProperty(DOMXPath $xpath, string $property): ?string
    {
        foreach ($xpath->query('//meta[@property]') as $meta) {
            /** @var DOMElement $meta */
            if (strtolower($meta->getAttribute('property')) === $property) {
                $value = $this->clean($meta->getAttribute('content'));
                return $value !== '' ? mb_substr($value, 0, 255) : null;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function structuredDataTypes(DOMXPath $xpath): array
    {
        $types = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $data = json_decode($script->textContent, true);
            if (!is_array($data)) {
                continue;
            }
            $items = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : (array_is_list($data) ? $data : [$data]);
            foreach ($items as $item) {
                if (is_array($item) && isset($item['@type'])) {
                    foreach ((array) $item['@type'] as $type) {
                        if (is_string($type)) {
                            $types[] = mb_substr($type, 0, 60);
                        }
                    }
                }
            }
        }

        return array_values(array_slice(array_unique($types), 0, 10));
    }

    /** @return list<string> */
    private function texts(DOMXPath $xpath, string $query, int $limit): array
    {
        $out = [];
        foreach ($xpath->query($query) as $node) {
            $text = $this->clean($node->textContent);
            if ($text !== '') {
                $out[] = mb_substr($text, 0, 200);
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    private function findPage(array $links, string $pattern): ?string
    {
        foreach ($links as $link) {
            $path = (string) parse_url($link['url'], PHP_URL_PATH);
            if (preg_match($pattern, $path) || preg_match($pattern, $link['text'])) {
                return $link['url'];
            }
        }

        return null;
    }

    private function sameSite(string $a, string $b): bool
    {
        $strip = fn (string $h) => preg_replace('/^www\./', '', $h);

        return $a !== '' && $strip($a) === $strip($b);
    }

    private function uniqueBy(array $items, string $key, int $limit): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            if (!isset($seen[$item[$key]])) {
                $seen[$item[$key]] = true;
                $out[] = $item;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    private function clean(?string $text): string
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
