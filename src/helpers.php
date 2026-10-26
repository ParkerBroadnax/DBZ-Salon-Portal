<?php

declare(strict_types=1);

/** Escape a value for safe output inside HTML text or attributes. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Read one trimmed text field from form input; arrays (e.g. name[]=x) and other non-strings count as empty. */
function text_input(array $input, string $key): string
{
    $value = $input[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Keep only the named fields that are plain strings, for refilling a form after a failed submit. */
function old_input(array $input, array $keys): array
{
    return array_filter(array_intersect_key($input, array_flip($keys)), 'is_string');
}
