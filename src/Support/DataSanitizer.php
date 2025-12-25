<?php

namespace Dibakar\ActivityScope\Support;

class DataSanitizer
{
    private array $sensitiveKeys;
    private array $config;

    public function __construct()
    {
        $this->config = config('activityscope.privacy', [
            'sanitize_data' => true,
            'sensitive_fields' => [],
            'track_ip_address' => true,
            'anonymize_ip' => false,
            'track_user_agent' => true,
            'hide_sensitive' => true,
        ]);

        $this->sensitiveKeys = array_merge(
            $this->config['sensitive_fields'] ?? [],
            [
                'password',
                'password_confirmation',
                'secret',
                'token',
                'key',
                'card',
                'cvv',
                'cvc',
                'pin',
                'api_key',
                'private_key',
                'credit_card',
                'ssn',
            ]
        );
    }

    public function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = $this->sanitizeValue($value, $key);
            }
            return $sanitized;
        }

        return $data;
    }

    public function shouldTrackIp(): bool
    {
        return $this->config['track_ip_address'] ?? true;
    }

    public function shouldAnonymizeIp(): bool
    {
        return $this->config['anonymize_ip'] ?? false;
    }

    public function shouldTrackUserAgent(): bool
    {
        return $this->config['track_user_agent'] ?? true;
    }

    public function shouldHideSensitive(): bool
    {
        return $this->config['hide_sensitive'] ?? true;
    }

    public function isSanitizeDataEnabled(): bool
    {
        return $this->config['sanitize_data'] ?? true;
    }

    private function sanitizeValue(mixed $value, string $key): mixed
    {
        if ($this->shouldHideSensitive() && $this->isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            return $this->sanitize($value);
        }

        if (is_string($value)) {
            $value = e($value);

            if ($this->shouldHideSensitive() && $this->looksLikeSecret($value)) {
                return substr($value, 0, 4)
                    . str_repeat('*', max(0, strlen($value) - 8))
                    . substr($value, -4);
            }
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitive) {
            if (str_contains($key, strtolower($sensitive))) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSecret(string $value): bool
    {
        if (strlen($value) < 20) {
            return false;
        }

        $patterns = [
            '/^[a-z0-9]{32,}$/i', // Hash-like
            '/^sk_[a-z0-9_]+$/i', // Stripe-like (improved)
            '/^pk_[a-z0-9_]+$/i', // API key-like (improved)
            '/^Bearer\\s+[a-z0-9_\\-\\.]+$/i', // Bearer token (improved)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}