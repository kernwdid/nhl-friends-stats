<?php

namespace Tests\Feature;

use App\Http\Controllers\VisionController;
use Tests\TestCase;

class VisionCredentialsTest extends TestCase
{
    public function test_missing_credentials_return_a_readable_error(): void
    {
        config(['services.google_vision.credentials' => '/missing/vision-service-account.json']);
        $result = app(VisionController::class)->getNHLResultFromImage('image');
        $this->assertStringContainsString('fehlt oder ist nicht lesbar', $result);
    }

    public function test_invalid_credentials_do_not_crash_or_expose_their_content(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vision-test-');
        try {
            file_put_contents($path, '{"private_key":"test-secret'."\n".'"}');
            config(['services.google_vision.credentials' => $path]);
            $result = app(VisionController::class)->getNHLResultFromImage('image');
            $this->assertStringContainsString('konnte nicht geladen werden', $result);
            $this->assertStringNotContainsString('test-secret', $result);
        } finally {
            unlink($path);
        }
    }
}
