<?php

declare(strict_types=1);

/** Escape a value for safe output inside HTML text or attributes. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
