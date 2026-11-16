<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyConfirmationLinkTest extends TestCase
{
    public function test_existing_ci3_link_keeps_token_and_opens_laravel_form(): void
    {
        $token = str_repeat('a1', 16);
        $this->get('/konfirmasi_kehadiran/'.$token)->assertRedirect('/konfirmasi/'.$token)
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_invalid_tokens_and_old_submission_paths_are_not_redirected(): void
    {
        $this->get('/konfirmasi_kehadiran/invalid')->assertNotFound();
        $this->post('/konfirmasi_kehadiran/kirim/'.str_repeat('a1', 16))->assertNotFound();
    }
}
