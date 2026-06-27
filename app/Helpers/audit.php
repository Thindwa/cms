<?php

if (! function_exists('highlight_json')) {
    function highlight_json(string $json): string
    {
        $json = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

        $json = preg_replace('/("(?:[^"\\\\]|\\\\.)*")\s*:/', '<span class="json-key">$1</span>:', $json);
        $json = preg_replace('/:\s*("(?:[^"\\\\]|\\\\.)*")/', ': <span class="json-string">$1</span>', $json);
        $json = preg_replace('/:\s*(\d+(?:\.\d+)?)/', ': <span class="json-number">$1</span>', $json);
        $json = preg_replace('/:\s*(true|false)/', ': <span class="json-bool">$1</span>', $json);
        $json = preg_replace('/:\s*null/', ': <span class="json-null">null</span>', $json);

        return $json;
    }
}
