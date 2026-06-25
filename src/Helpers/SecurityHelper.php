<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Sanitize a string for safe HTML output.
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize a string by removing potentially dangerous characters.
     */
    public static function sanitizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        // Remove tags perigosas
        $value = strip_tags($value);
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $value;
    }

    /**
     * Sanitize email address.
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize integer value.
     */
    public static function sanitizeInt($value): int
    {
        $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        return (int)($filtered === null || $filtered === '' ? 0 : $filtered);
    }
}
