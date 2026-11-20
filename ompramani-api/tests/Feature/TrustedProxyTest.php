<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_behind_reverse_proxy_with_https_forwarded_proto(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'ompramani.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/up');

        $response->assertStatus(200);

        $request = request();
        $this->assertTrue($request->isSecure());
        $this->assertSame('https', $request->getScheme());
        $this->assertSame('ompramani.onrender.com', $request->getHost());

        // Verify URL generator produces https
        $this->assertStringStartsWith('https://ompramani.onrender.com', url('admin/login'));
        $this->assertStringStartsWith('https://ompramani.onrender.com', asset('css/filament/filament/app.css'));
    }

    public function test_filament_admin_login_renders_secure_assets_under_proxy(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'ompramani.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/admin/login');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Must not contain insecure http references to assets on ompramani.onrender.com
        $this->assertStringNotContainsString('http://ompramani.onrender.com/css/filament', $content);
        $this->assertStringNotContainsString('http://ompramani.onrender.com/js/filament', $content);
        $this->assertStringNotContainsString('http://ompramani.onrender.com/livewire', $content);
        $this->assertStringNotContainsString('http://ompramani.onrender.com/vendor/livewire', $content);

        // Must contain secure https references or relative asset paths
        $this->assertStringContainsString('filament', $content);
    }

    public function test_api_pagination_generates_https_urls_under_proxy(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'ompramani.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/up');

        // Create a paginator in the request context
        $paginator = new LengthAwarePaginator(
            items: ['item1', 'item2'],
            total: 10,
            perPage: 2,
            currentPage: 1,
            options: ['path' => url('api/v1/articles')]
        );

        $paginationArray = $paginator->toArray();

        $this->assertStringStartsWith('https://ompramani.onrender.com/api/v1/articles', $paginationArray['first_page_url']);
        $this->assertStringStartsWith('https://ompramani.onrender.com/api/v1/articles', $paginationArray['next_page_url']);
    }
}
