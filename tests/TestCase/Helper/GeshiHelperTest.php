<?php
declare(strict_types=1);

namespace Geshi\TestCase\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Geshi\View\Helper\GeshiHelper;

class GeshiHelperTest extends TestCase
{
    protected $settings = [
        'set_header_type' => ['GESHI_HEADER_NONE'],
        'enable_line_numbers' => ['GESHI_FANCY_LINE_NUMBERS', 2],
        'enable_classes' => [],
        'set_tab_width' => [4],
    ];

    protected $view;
    protected $configPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['Geshi']);

        $this->view = $this->getMockBuilder(View::class)->getMock();
        $this->geshi = new GeshiHelper($this->view);
        $this->configPath = $this->geshi->configPath = dirname(dirname(dirname(__FILE__))) . DS;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->view, $this->geshi);
    }

    /**
     * Run the tests against the configuration variants.
     *
     * @param string $method The test method to run.
     */
    public function runVariants($method)
    {
        // Using a config file, traditional.
        $this->geshi = new GeshiHelper($this->view);
        $this->geshi->configPath = $this->configPath;
        call_user_func([$this, $method]);
        unset($this->geshi);

        // Pre-configuration during instantiation, such as from controller.
        $this->geshi = new GeshiHelper($this->view, $this->settings);
        unset($this->geshi->configPath);
        call_user_func([$this, $method]);
        unset($this->geshi);

        // Configuration on the fly, such as from view.
        $this->geshi = new GeshiHelper($this->view);
        unset($this->geshi->configPath);
        $this->geshi->features = $this->settings;
        call_user_func([$this, $method]);
        unset($this->geshi);
    }

    /**
     * Test highlighting with variants.
     *
     * @return void
     */
    public function testConfigVariants()
    {
        $this->runVariants('testPlainTextButton');
    }

    /**
     * Test basic highlighting
     *
     * @return void
     */
    public function testHighlight()
    {
        $this->geshi->showPlainTextButton = false;

        // Simple one code block
        $text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
        $result = $this->geshi->highlight($text);
        $expected = [
            '<p', 'This is some text', '/p',
            'div' => ['class' => 'code', 'lang' => 'php'],
                'ol' => ['class' => 'php'],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/div',
            '<p', 'More text', '/p',
        ];
        $this->assertHtml($expected, $result);

        // Two code blocks
        $text = '<p>Some text</p><pre lang="php"><?php echo $foo; ?></pre><p>text</p><pre lang="php"><?php echo $bar; ?></pre><p>Even more text</p>';
        $result = $this->geshi->highlight($text);

        $expected = [
            '<p', 'Some text', '/p',
            ['div' => ['class' => 'code', 'lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
                '/div',
                '<p', 'text', '/p',
                ['div' => ['class' => 'code', 'lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$bar', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/div',
            '<p', 'Even more text', '/p',
        ];
        $this->assertHtml($expected, $result);

        // Codeblock with single quotes Fails because of issues in CakeTestCase::assertHtml()
        $text = '<pre lang=\'php\'><?php echo $foo = "foo"; ?></pre>';
        $result = $this->geshi->highlight($text);
        $expected = [
            ['div' => ['class' => 'code', 'lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        // More than one valid code block container
        $this->geshi->validContainers = ['pre', 'code'];
        $text = '<pre lang="php"><?php echo $foo = "foo"; ?></pre><p>Text</p><code lang="php">echo $foo = "foo";</code>';
        $result = $this->geshi->highlight($text);
        $expected = [
            ['div' => ['class' => 'code', 'lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/div',
            '<p', 'Text', '/p',
            ['code' => ['lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/code',
        ];
        $this->assertHtml($expected, $result, true);

        // No valid languages no highlights
        $this->geshi->validContainers = ['pre'];
        $this->geshi->validLanguages = [];
        $text = '<p>text</p><pre lang="php">echo $foo;</pre><p>text</p>';
        $result = $this->geshi->highlight($text);
        $expected = [
            '<p', 'text', '/p',
                'div' => ['class' => 'code', 'lang' => 'php'],
                    'echo $foo;',
                '/div',
            '<p', 'text', '/p',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the inclusion of the toggle text button
     *
     * @return void
     */
    public function testPlainTextButton()
    {
        // Simple one code block
        $text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
        $result = $this->geshi->highlight($text);
        $expected = [
            '<p', 'This is some text', '/p',
            'a' => ['href' => '#null', 'class' => 'geshi-plain-text'], 'Show Plain Text', '/a',
            ['div' => ['class' => 'code', 'lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/div',
            '<p', 'More text', '/p',
        ];
        $this->assertHtml($expected, $result);
    }

    public function testNoTagReplacement()
    {
        // Simple one code block
        $this->geshi->showPlainTextButton = false;
        $this->geshi->containerMap = [];

        $text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
        $result = $this->geshi->highlight($text);
        $expected = [
            '<p', 'This is some text', '/p',
            ['pre' => ['lang' => 'php']],
                ['ol' => ['class' => 'php']],
                    ['li' => ['class' => 'li1']],
                        ['div' => ['class' => 'de1']],
                            ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            ['span' => ['class' => 'kw1']], 'echo', '/span',
                            ['span' => ['class' => 're0']], '$foo', '/span',
                            ['span' => ['class' => 'sy0']], '=', '/span',
                            ['span' => ['class' => 'st0']], '&quot;foo&quot;', '/span',
                            ['span' => ['class' => 'sy0']], ';', '/span',
                            ['span' => ['class' => 'sy1']], '?&gt;', '/span',
                        '/div',
                    '/li',
                '/ol',
            '/pre',
            '<p', 'More text', '/p',
        ];
        $this->assertHtml($expected, $result);
    }

    public function testHighlightText()
    {
        $result = $this->geshi->highlightText("<?php echo 'test';", 'php');
        $expected = [
            ['ol' => ['class' => 'php']],
                ['li' => ['class' => 'li1']],
                    ['div' => ['class' => 'de1']],
                        ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                        ['span' => ['class' => 'kw1']], 'echo', '/span',
                        ['span' => ['class' => 'st_h']], "'test'", '/span',
                        ['span' => ['class' => 'sy0']], ';', '/span',
                    '/div',
                '/li',
            '/ol',
        ];
        $this->assertHtml($expected, $result);
    }

    public function testHighlightAsTable()
    {
        $text = <<<CODE
<?php
echo 'test';
echo 1 + 1;
CODE;
        $result = $this->geshi->highlightAsTable($text, 'php');
        $expected = [
            ['table' => ['class' => 'code', 'cellspacing' => 0, 'cellpadding' => 0]],
                '<tbody',
                    '<tr',
                        ['td' => ['class' => 'code-numbers']],
                            ['div' => ['class' => 'de1']], 1, '/div',
                            ['div' => ['class' => 'de1']], 2, '/div',
                            ['div' => ['class' => 'de1']], 3, '/div',
                        '/td',
                        ['td' => ['class' => 'code-block']],
                            ['div' => ['class' => 'de1']],
                                ['span' => ['class' => 'kw2']], '&lt;?php', '/span',
                            '/div',
                            ['div' => ['class' => 'de1']],
                                ['span' => ['class' => 'kw1']], 'echo', '/span',
                                ['span' => ['class' => 'st_h']], "'test'", '/span',
                                ['span' => ['class' => 'sy0']], ';', '/span',
                            '/div',
                            ['div' => ['class' => 'de1']],
                                ['span' => ['class' => 'kw1']], 'echo', '/span',
                                ['span' => ['class' => 'nu0']], '1', '/span',
                                ['span' => ['class' => 'sy0']], '+', '/span',
                                ['span' => ['class' => 'nu0']], '1', '/span',
                                ['span' => ['class' => 'sy0']], ';', '/span',
                            '/div',
                        '/td',
                    '/tr',
                '/tbody',
            '/table',
        ];
        $this->assertHtml($expected, $result);
    }

    public function testTemplates()
    {
        $this->geshi->setConfig('templates', [
            'layout' => '<w>{{showplain}}{{open}}{{content}}{{close}}</w>',
            // The highlighted text. Provided so wrapping markup can be added.
            'content' => '<c>{{code}}</c>',
            // The button element and wrappers used for showPlainTextButton
            'showplain' => '<a href="#null">Plain Text</a>',
        ]);
        $this->geshi->validContainers = ['pre'];
        $this->geshi->validLanguages = [];
        $text = '<p>text</p><pre lang="php">echo $foo;</pre><p>text</p>';
        $result = $this->geshi->highlight($text);
        $expected = [
            '<p', 'text', '/p',
            '<w',
            ['a' => ['href' => '#null']], 'Plain Text', '/a',
            ['div' => ['class' => 'code', 'lang' => 'php']],
            '<c',
            'echo $foo;',
            '/c',
            '/div',
            '/w',
            '<p', 'text', '/p',
        ];
        $this->assertHtml($expected, $result);
    }
}
