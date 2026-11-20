<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root URL redirects directly to Filament Admin.
     */
    public function test_the_application_redirects_root_to_admin(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    /**
     * Accidental browser URL prefix typos redirect gracefully to intended destination.
     */
    public function test_accidental_url_typos_redirect_to_target(): void
    {
        $response = $this->get('/https:/admin/login');

        $response->assertRedirect('/admin/login');
    }
}
