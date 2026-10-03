<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    /**
     * Test that all required favicon and touch assets exist in the public directory
     * with valid dimensions, proper binary structure, and non-empty sizes.
     */
    public function test_all_favicon_assets_exist_with_valid_content(): void
    {
        $publicDir = public_path();

        // 1. Verify favicon.ico exists and has multi-resolution payload (> 1KB)
        $icoPath = $publicDir . DIRECTORY_SEPARATOR . 'favicon.ico';
        $this->assertFileExists($icoPath, 'favicon.ico must exist in public directory.');
        $this->assertGreaterThan(1000, filesize($icoPath), 'favicon.ico must not be empty and should contain multi-resolution icon frames.');

        // Verify ICO binary header (0x0000 0x0001)
        $icoHandle = fopen($icoPath, 'rb');
        $header = fread($icoHandle, 4);
        fclose($icoHandle);
        $this->assertSame("\x00\x00\x01\x00", substr($header, 0, 4), 'favicon.ico must start with valid Windows ICO magic bytes.');

        // 2. Verify favicon.svg exists and contains valid SVG XML with medical cross
        $svgPath = $publicDir . DIRECTORY_SEPARATOR . 'favicon.svg';
        $this->assertFileExists($svgPath, 'favicon.svg must exist in public directory.');
        $svgContent = file_get_contents($svgPath);
        $this->assertStringContainsString('<svg', $svgContent);
        $this->assertStringContainsString('fmsEmeraldGrad', $svgContent);
        $this->assertStringContainsString('viewBox="0 0 512 512"', $svgContent);

        // 3. Verify standard PNG favicons (16x16, 32x32, 48x48)
        $pngSizes = [
            'favicon-16x16.png' => [16, 16],
            'favicon-32x32.png' => [32, 32],
            'favicon-48x48.png' => [48, 48],
            'apple-touch-icon.png' => [180, 180],
            'android-chrome-192x192.png' => [192, 192],
            'android-chrome-512x512.png' => [512, 512],
        ];

        foreach ($pngSizes as $filename => [$expectedW, $expectedH]) {
            $filePath = $publicDir . DIRECTORY_SEPARATOR . $filename;
            $this->assertFileExists($filePath, "{$filename} must exist in public directory.");
            $this->assertGreaterThan(200, filesize($filePath), "{$filename} must have valid content size.");

            [$w, $h] = getimagesize($filePath);
            $this->assertSame($expectedW, $w, "{$filename} width must be {$expectedW}px.");
            $this->assertSame($expectedH, $h, "{$filename} height must be {$expectedH}px.");
        }

        // 4. Verify site.webmanifest is valid JSON with emerald theme color
        $manifestPath = $publicDir . DIRECTORY_SEPARATOR . 'site.webmanifest';
        $this->assertFileExists($manifestPath, 'site.webmanifest must exist.');
        $manifestJson = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifestJson, 'site.webmanifest must be valid JSON.');
        $this->assertSame('#059669', $manifestJson['theme_color']);
        $this->assertNotEmpty($manifestJson['icons']);

        // 5. Verify browserconfig.xml is valid XML
        $xmlPath = $publicDir . DIRECTORY_SEPARATOR . 'browserconfig.xml';
        $this->assertFileExists($xmlPath, 'browserconfig.xml must exist.');
        $xmlContent = file_get_contents($xmlPath);
        $this->assertStringContainsString('<browserconfig>', $xmlContent);
        $this->assertStringContainsString('#059669', $xmlContent);
    }

    /**
     * Test that the Blade component <x-favicon /> renders all expected link and meta tags.
     */
    public function test_favicon_blade_component_renders_complete_tags(): void
    {
        $rendered = Blade::render('<x-favicon />');

        $this->assertStringContainsString('rel="icon" type="image/svg+xml"', $rendered);
        $this->assertStringContainsString('favicon.svg', $rendered);
        $this->assertStringContainsString('rel="icon" type="image/png" sizes="32x32"', $rendered);
        $this->assertStringContainsString('favicon-32x32.png', $rendered);
        $this->assertStringContainsString('rel="icon" type="image/png" sizes="16x16"', $rendered);
        $this->assertStringContainsString('favicon-16x16.png', $rendered);
        $this->assertStringContainsString('rel="shortcut icon"', $rendered);
        $this->assertStringContainsString('favicon.ico', $rendered);
        $this->assertStringContainsString('rel="apple-touch-icon" sizes="180x180"', $rendered);
        $this->assertStringContainsString('apple-touch-icon.png', $rendered);
        $this->assertStringContainsString('name="theme-color" content="#059669"', $rendered);
        $this->assertStringContainsString('name="msapplication-TileColor" content="#059669"', $rendered);
        $this->assertStringNotContainsString('rel="manifest"', $rendered);
    }

    /**
     * Test that the login page includes the integrated favicon component.
     */
    public function test_login_page_includes_favicon(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('favicon.svg', false);
        $response->assertSee('favicon.ico', false);
    }
}
