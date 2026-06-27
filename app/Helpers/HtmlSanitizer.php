<?php

namespace App\Helpers;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><s><sub><sup><ol><ul><li><blockquote><pre><code><h1><h2><h3><h4><h5><h6><span><a><hr><table><thead><tbody><tr><th><td><img>';

    public static function clean(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $cleaned = strip_tags($value, self::ALLOWED_TAGS);

        $cleaned = preg_replace('/<a\s[^>]*href\s*=\s*["\']javascript:[^"\']*["\'][^>]*>.*?<\/a>/i', '', $cleaned);
        $cleaned = preg_replace('/<a\s[^>]*on\w+\s*=\s*["\'][^"\']*["\'][^>]*>.*?<\/a>/i', '', $cleaned);
        $cleaned = preg_replace('/<img\s[^>]*on\w+\s*=\s*["\'][^"\']*["\'][^>]*\/?>/i', '', $cleaned);
        $cleaned = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);

        return trim($cleaned);
    }
}
