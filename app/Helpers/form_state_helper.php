<?php

declare(strict_types=1);

if (! function_exists('field_error')) {
    /**
     * @param array<string, string> $errors
     */
    function field_error(array $errors, string $field): ?string
    {
        return $errors[$field] ?? null;
    }
}

if (! function_exists('field_error_class')) {
    /**
     * @param array<string, string> $errors
     */
    function field_error_class(array $errors, string $field, string $class = 'is-invalid'): string
    {
        return array_key_exists($field, $errors) ? $class : '';
    }
}

if (! function_exists('old_bool')) {
    function old_bool(string $field, bool $default = false): bool
    {
        $value = old($field);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }
}
