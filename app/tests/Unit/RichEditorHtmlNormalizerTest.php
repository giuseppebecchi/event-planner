<?php

namespace Tests\Unit;

use App\Support\RichEditorHtmlNormalizer;
use PHPUnit\Framework\TestCase;

class RichEditorHtmlNormalizerTest extends TestCase
{
    public function test_it_wraps_plain_list_item_text_in_paragraphs(): void
    {
        $html = '<h2>Conditions</h2><ul><li>First condition.</li><li>Second condition.</li></ul>';

        $this->assertSame(
            '<h2>Conditions</h2><ul><li><p>First condition.</p></li><li><p>Second condition.</p></li></ul>',
            RichEditorHtmlNormalizer::normalizeListItems($html),
        );
    }

    public function test_it_keeps_existing_paragraph_list_items_unchanged(): void
    {
        $html = '<ul><li><p>Editable item.</p></li><li><p>Another item.</p></li></ul>';

        $this->assertSame($html, RichEditorHtmlNormalizer::normalizeListItems($html));
    }

    public function test_it_preserves_inline_formatting_inside_list_items(): void
    {
        $html = '<ul><li>Payment by <strong>bank transfer</strong>.</li></ul>';

        $this->assertSame(
            '<ul><li><p>Payment by <strong>bank transfer</strong>.</p></li></ul>',
            RichEditorHtmlNormalizer::normalizeListItems($html),
        );
    }

    public function test_it_preserves_utf8_punctuation(): void
    {
        $html = '<p>The “Contract” VAT n° test.</p><ul><li>Client’s email</li></ul>';

        $this->assertSame(
            '<p>The “Contract” VAT n° test.</p><ul><li><p>Client’s email</p></li></ul>',
            RichEditorHtmlNormalizer::normalizeListItems($html),
        );
    }
}
