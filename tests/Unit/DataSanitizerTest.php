<?php

namespace Dibakar\ActivityScope\Tests\Unit;

use Dibakar\ActivityScope\Support\DataSanitizer;
use Dibakar\ActivityScope\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class DataSanitizerTest extends TestCase
{
    /** @test */
    public function test_it_sanitizes_nested_arrays()
    {
        Config::set('activityscope.privacy.sensitive_fields', ['secret']);

        $sanitizer = new DataSanitizer();

        $data = [
            'public' => 'value',
            'nested' => [
                'secret_key' => 'shhh',
                'deep' => [
                    'password' => '123456'
                ]
            ]
        ];

        $sanitized = $sanitizer->sanitize($data);

        $this->assertEquals('value', $sanitized['public']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['secret_key']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['deep']['password']);
    }

    /** @test */
    public function test_it_masks_secret_looking_strings()
    {
        $sanitizer = new DataSanitizer();
        $secret = 'sk_test_4eC39HqLyjWDarjtT1zdp7dc';

        // Ensure it is redacted if detected as secret-like in a string context
        $sanitized = $sanitizer->sanitize(['api_key' => $secret]);

        // Since 'api_key' is in the default sensitive list, it will be fully redacted
        $this->assertEquals('[REDACTED]', $sanitized['api_key']);

        // Test standard string detection without key match
        // Note: The looksLikeSecret check runs for strings. 
        // However, if the key matches sensitive list, it returns '[REDACTED]' immediately.
        // We need a key that isn't sensitive but value looks like a secret.

        $sanitizedValue = $sanitizer->sanitize(['description' => $secret]);

        // Should be masked like "sk_t************************7dc"
        $this->assertStringStartsWith('sk_t', $sanitizedValue['description']);
        $this->assertStringEndsWith('7dc', $sanitizedValue['description']);
        $this->assertStringContainsString('*', $sanitizedValue['description']);
    }

    /** @test */
    public function test_it_respects_hide_sensitive_config()
    {
        Config::set('activityscope.privacy.hide_sensitive', false);

        $sanitizer = new DataSanitizer();
        $data = ['password' => 'secret'];

        $this->assertEquals('secret', $sanitizer->sanitize($data)['password']);
    }

    /** @test */
    public function test_it_returns_input_if_not_array()
    {
        $sanitizer = new DataSanitizer();
        $this->assertEquals('string', $sanitizer->sanitize('string'));
        $this->assertEquals(123, $sanitizer->sanitize(123));
    }
}
