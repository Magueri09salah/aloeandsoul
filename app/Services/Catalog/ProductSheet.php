<?php

namespace App\Services\Catalog;

use InvalidArgumentException;

/**
 * A product information file (database/assets/products/<slug>/details.md):
 *
 *     ---
 *     reference: 815
 *     price: 425
 *     ---
 *
 *     # Description
 *     Markdown…
 *
 *     # Images
 *     - 01.webp | Alternative text
 *
 * Front matter is flat "key: value" lines. Each "# Heading" starts a section
 * whose body is Markdown (use ### for headings inside a section).
 */
final class ProductSheet
{
    /**
     * @param  array<string, string>  $meta
     * @param  array<string, string>  $sections  lower-cased heading => Markdown
     */
    private function __construct(
        public readonly array $meta,
        public readonly array $sections,
    ) {}

    public static function fromFile(string $path): self
    {
        $text = @file_get_contents($path);
        if ($text === false) {
            throw new InvalidArgumentException("Cannot read {$path}.");
        }

        return self::parse($text);
    }

    public static function parse(string $text): self
    {
        $text = str_replace("\r\n", "\n", $text);
        if (! preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $text, $m)) {
            throw new InvalidArgumentException('A product sheet starts with a front matter block between --- lines.');
        }

        $meta = [];
        foreach (explode("\n", $m[1]) as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$key, $value] = array_pad(explode(':', $line, 2), 2, null);
            if ($value === null) {
                throw new InvalidArgumentException("Front matter line without a colon: {$line}");
            }
            $meta[trim($key)] = trim($value);
        }

        $sections = [];
        $current = null;
        foreach (explode("\n", $m[2]) as $line) {
            if (preg_match('/^# (.+)$/', $line, $h)) {
                $current = strtolower(trim($h[1]));
                $sections[$current] = '';

                continue;
            }
            if ($current !== null) {
                $sections[$current] .= $line."\n";
            }
        }

        return new self($meta, array_map('trim', $sections));
    }

    public function meta(string $key): ?string
    {
        $value = $this->meta[$key] ?? null;

        return $value === null || $value === '' ? null : $value;
    }

    public function section(string $heading): ?string
    {
        $value = $this->sections[strtolower($heading)] ?? null;

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * Option name => reference, from "variants: Vanilla=475; Chocolate=476".
     *
     * @return array<string, string>
     */
    public function variants(): array
    {
        $variants = [];
        foreach (array_filter(array_map('trim', explode(';', (string) $this->meta('variants')))) as $pair) {
            [$name, $reference] = array_pad(array_map('trim', explode('=', $pair, 2)), 2, '');
            if ($name !== '' && $reference !== '') {
                $variants[$name] = $reference;
            }
        }

        return $variants;
    }

    /**
     * The "# Images" section: "- file | alt" lines, in display order.
     *
     * @return list<array{file: string, alt: string}>
     */
    public function images(): array
    {
        $images = [];
        foreach (explode("\n", (string) $this->section('images')) as $line) {
            if (preg_match('/^-\s+(\S+)\s*\|\s*(.+)$/', trim($line), $m)) {
                $images[] = ['file' => $m[1], 'alt' => trim($m[2])];
            }
        }

        return $images;
    }
}
