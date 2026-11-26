<?php

namespace Tests\Unit;

use App\Services\Exports\TiptapHtmlRenderer;
use Tests\TestCase;

class TiptapHtmlRendererTest extends TestCase
{
    public function test_relative_images_are_rendered(): void
    {
        $html = (new TiptapHtmlRenderer)->render([
            'type' => 'doc',
            'content' => [[
                'type' => 'image',
                'attrs' => ['src' => '/images/company-logo.png', 'alt' => 'Logo'],
            ]],
        ]);

        $this->assertStringContainsString('/images/company-logo.png', $html);
    }

    public function test_external_images_are_not_rendered_for_export(): void
    {
        $html = (new TiptapHtmlRenderer)->render([
            'type' => 'doc',
            'content' => [[
                'type' => 'image',
                'attrs' => ['src' => 'http://169.254.169.254/latest/meta-data', 'alt' => 'metadata'],
            ]],
        ]);

        $this->assertStringNotContainsString('169.254.169.254', $html);
    }
}
