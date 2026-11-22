<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once 'util.php';

final class FixDescTest extends TestCase
{
    public static function descriptions(): array
    {
        return [
            # Do nothing
            ["hello world", "hello world"],
            ["hello <I>world</I>", "hello <I>world</I>"],
            ["hello <I>world</I> <I>bla</i> bla", "hello <I>world</I> <I>bla</i> bla"],
            ["hello <b><I>world</I></b> <I>bla</i> bla", "hello <b><I>world</I></b> <I>bla</i> bla"],
            ["hello <b><I><u>world</u></I></b> <I>bla</i> bla", "hello <b><I><u>world</u></I></b> <I>bla</i> bla"],
            ["hello <p>world</p>", "hello <p>world</p>"],

            # Add tags at the end
            ["hello <I>world bla bla", "hello <I>world bla bla</i>"],
            ["hello <I><b>world</b> bla bla", "hello <I><b>world</b> bla bla</i>"],
            ["hello <I><u><b>world</b> bla bla", "hello <I><u><b>world</b> bla bla</u></i>"],

            # Tags in the wrong order -- this is just documenting the current behavior. I don't know if it's desired.
            # It's probably not this function's job to fix bad HTML
            ["hello <I><B>world</I></B>", "hello <I><B>world</b></I></B>"],

            # Unknown tag
            ["hello <unknown><I>world</I>", "hello &lt;unknown&gt;<I>world</I>"],

            # Removed spoiler tag (in regular mode)
            ["hello <spoiler>secret</spoiler> <I>world</I>", "hello  <I>world</I>"],

            # Replaced spoiler tag (in RSS mode)
            ["hello <spoiler>secret</spoiler> <I>world</I>", "hello [spoilers] <I>world</I>", FixDescRSS],

            # Stray spoiler close tags are dropped
            ["<p>a</spoiler> b</p>", "<p>a b</p>", FixDescSpoiler],
            ["<b></spoiler            >xyz</b>", "<b>xyz</b>", FixDescSpoiler],
            ["a </spoiler>b", "a b"],
            ["a </spoiler>b", "a b", FixDescRSS],

            # Strip newlines
            ["\nhello world\n", "hello world"],
            ["\r\nhello world\r\n", "hello world"],

            # Attributes are dropped from allowed tags
            ["<b onmouseover=\"alert(1)\">x</b>", "<b>x</b>"],
            ["<I ONCLICK='alert(1)'>x</I>", "<i>x</I>"],
            ["<p style=\"position:fixed\">x</p>", "<p>x</p>"],
            ["<p class=x id=y>x", "<p>x</p>"],
            ["<b onclick=alert(1)/>x", "<b/>x"],
            ["<br onclick=alert(1)>x", "<br>x"],
            ["<hr style=\"display:none\">", "<hr></hr>"],
            ["<b>x</b onclick=alert(1)>", "<b>x</b>"],
            ["<b title=\"a>b\" onclick=alert(1)>x</b>", "<b>b&quot; onclick=alert(1)&gt;x</b>"],
            ["<code class=\"language-php\">x</code>", "<code>x</code>"],
            ["<ol start=\"3\"><li>x</ol>", "<ol start=\"3\"><li>x</ol>"],
            ["<ol start=\"3\" onclick=\"alert(1)\"><li>x</ol>", "<ol><li>x</ol>"],
            ["<ol start=\"javascript:alert(1)\"><li>x</ol>", "<ol><li>x</ol>"],
            ["<ol start=1000000><li>x</ol>", "<ol start=\"1000000\"><li>x</ol>"],
            ["<ol title=\"x start=7\"><li>x</ol>", "<ol><li>x</ol>"],
            ["<ul type=disc><li>x</ul>", "<ul><li>x</ul>"],
            ["<b\n>x</b>", "<b>x</b>"],
            ["<b/\0>x", "<b/>x"],
            ["<b >x</b >", "<b>x</b>"],
            ["<h1 class=\"x\">t</h1>", "<h1>t</h1>"],
            ["<h1 class=\"x\">t</h1>", "<h4>t</h4>", FixDescDemoteHeadings],
            ["<h1 class=\"aaaaaaaaaaaaaaaaaaaaaaaa\"><img src=x onerror=alert(1)>", "<h4>&lt;img src=x onerror=alert(1)&gt;</h4>", FixDescDemoteHeadings],
            ["<h2>a</h2><h3>b", "<h5>a</h5><h6>b</h6>", FixDescDemoteHeadings],
            ["<spoiler onclick=alert(1)>x</spoiler>y", "y"],

            # Tags with junk after the name aren't allowed
            ["<b/onclick=alert(1)>x", "&lt;b/onclick=alert(1)&gt;x"],
            ["<b\tonclick=alert(1)>x", "&lt;b onclick=alert(1)&gt;x"],
            ["<img src=x onerror=alert(1)>", "&lt;img src=x onerror=alert(1)&gt;"],
            ["<script>alert(1)</script>", "&lt;script&gt;alert(1)&lt;/script&gt;"],
            ["<svg onload=alert(1)>", "&lt;svg onload=alert(1)&gt;"],

            # Links
            ["<a href=\"https://ifdb.org/\">x</a>", "<a href=\"https://ifdb.org/\">x</a>"],
            ["<a href='http://ifdb.org/'>x</a>", "<a href='http://ifdb.org/'>x</a>"],
            ["<a game=\"abc123\">x</a>", "<a href=\"viewgame?id=abc123\">x</a>"],
            ["<a href=\"javascript:alert(1)\">x</a>", "&lt;a href=&quot;javascript:alert(1)&quot;&gt;x&lt;/a&gt;"],
            ["<a href=\" javascript:alert(1)\">x</a>", "&lt;a href=&quot; javascript:alert(1)&quot;&gt;x&lt;/a&gt;"],
            ["<a href=\"data:text/html,x\">x</a>", "&lt;a href=&quot;data:text/html,x&quot;&gt;x&lt;/a&gt;"],
            ["<a href=javascript:alert(1)>x</a>", "&lt;a href=javascript:alert(1)&gt;x&lt;/a&gt;"],
            ["<a href=\"https://ifdb.org/\" onclick=\"alert(1)\">x</a>", "&lt;a href=&quot;https://ifdb.org/&quot; onclick=&quot;alert(1)&quot;&gt;x&lt;/a&gt;"],
            ["<a href=\"https://ifdb.org/\" style=\"position:fixed\">x</a>", "&lt;a href=&quot;https://ifdb.org/&quot; style=&quot;position:fixed&quot;&gt;x&lt;/a&gt;"],
            ["<a onclick=\"alert(1)\" href=\"https://ifdb.org/\">x</a>", "&lt;a onclick=&quot;alert(1)&quot; href=&quot;https://ifdb.org/&quot;&gt;x&lt;/a&gt;"],

            # Line breaks
            ["a\nb", "a<br>b"],
            ["<p>a\nb", "<p>a\nb</p>"],
        ];
    }

    #[DataProvider('descriptions')]
    public function testFixDesc(string $input, string $expected, int $specials = 0): void
    {
        $this->assertSame($expected, fixDesc($input, $specials));
    }

    public function testSpoilerHoldingBlocksUsesDiv(): void
    {
        $this->assertStringContainsString('<span class="hiddenSpoiler"', fixDesc("<p>a <spoiler>b</spoiler></p>", FixDescSpoiler));
        $this->assertStringContainsString('<div class="hiddenSpoiler"', fixDesc("<p>a <spoiler>b</p><p>c</spoiler></p>", FixDescSpoiler));
        $this->assertStringContainsString('<div class="hiddenSpoiler"', fixDesc("<p>a <spoiler>b <br x/> c</spoiler></p>", FixDescSpoiler));
        $this->assertStringContainsString('hidden>b  c</span>', fixDesc("<p>a <spoiler>b </i> c</spoiler></p>", FixDescSpoiler));
    }

    public function testMarkdownInsideSpoiler(): void
    {
        $this->assertSame("<p><spoiler><em>a</em></spoiler></p>\n", markdownMultilineText("<spoiler>*a*</spoiler>"));
        $this->assertSame("<p><code>&lt;spoiler&gt;a&lt;/spoiler&gt;</code></p>\n", markdownMultilineText("`<spoiler>a</spoiler>`"));
        $this->assertSame("<ul>\n<li>\n<p>a</p>\n<spoiler>\n<p><em>b</em></p>\n</spoiler>\n</li>\n</ul>\n", markdownMultilineText("- a\n  <spoiler>\n  *b*\n  </spoiler>"));
        $this->assertSame("<blockquote>\n<spoiler>\n<p><em>b</em></p>\n</spoiler>\n</blockquote>\n", markdownMultilineText("> <spoiler>\n> *b*\n> </spoiler>"));
        $this->assertSame("<pre><code>&lt;spoiler&gt;\nb\n</code></pre>\n", markdownMultilineText("    <spoiler>\n    b"));
    }

    public function testMarkdownLine(): void
    {
        $this->assertSame("Title <em>a</em> b c", markdownLine("# Title\n\n*a*\nb  \n<H1>c</H1>"));
    }
}
