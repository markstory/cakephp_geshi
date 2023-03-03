<?php
declare(strict_types=1);

/**
 * Geshi Helper
 *
 * Implements geshi syntax highlighting for cakephp
 * Originally based off of http://www.gignus.com/code/code.phps
 *
 * @author Mark story
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @copyright 2008-2014 Mark Story <mark@mark-story.com>
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */
namespace Geshi\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use GeSHi;
use InvalidArgumentException;

/**
 * Expose Geshi Syntax highlighting in a CakePHP application.
 */
class GeshiHelper extends Helper
{
    /**
     * Configuration data
     *
     * - `configPath` - Path the configuration file can be found on.
     *   Configuration file will *not* be used if $features is set.
     * - `features` - GeSHi features this instance will use. Set GeSHi options.
     *   ex. `['set_header_type' => [2]]`
     * - `validContainers` - The Container Elements that could contain highlightable code.
     * - `containerMap` - Replace containers with divs to increase validation.
     * - `validLanguages` - The languages you want to highlight.
     * - `defaultLanguage` - Default language to use if no valid language is found.
     *   Leave null to require a language attribute to be set on each container.
     *   Can be string or false.
     * - `langAttribute` - Regexp to find the HTML attribute use for finding the code Language.
     * - `showPlainTextButton` - Show the Button that can be used with JS to switch to plain text.
     *
     * @param array
     */
    protected $_defaultConfig = [
        'configPath' => '',
        'features' => [],
        'validContainers' => ['pre'],
        'containerMap' => ['pre' => ['div class="code"', 'div']],
        'validLanguages' => [
            'css', 'html', 'php', 'javascript', 'python', 'sql', 'ruby', 'coffeescript',
            'bash', 'rust', 'go', 'c', 'yaml', 'sass', 'lua', 'dart', 'xml', 'json',
        ],
        'defaultLanguage' => false,
        'langAttribute' => '(?:lang|class)',
        'showPlainTextButton' => true,
    ];

    /**
     * GeSHi Instance
     *
     * @var \GeSHi|null
     */
    protected $_geshi = null;

    /**
     * Set the default features if any specified in $helpers
     *
     * @param \Cake\View\View $view Cake view
     * @param array $settings config data
     * @return void
     */
    public function __construct(View $view, array $settings = [])
    {
        $this->features = $settings;
        parent::__construct($view, $settings);
    }

    /**
     * Magic getter for backwards compatibility with public variables.
     *
     * @param string $name The attribute to read
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!array_key_exists($name, $this->_defaultConfig)) {
            throw new InvalidArgumentException("Invalid configuration key {$name}");
        }

        return $this->getConfig($name);
    }

    /**
     * Magic setter for backwards compatibility with public variables.
     *
     * @param string $name The attribute to set
     * @param mixed $value The attribute value
     * @return void
     */
    public function __set(string $name, $value)
    {
        if (!array_key_exists($name, $this->_defaultConfig)) {
            throw new InvalidArgumentException("Invalid configuration key {$name}");
        }

        $this->setConfig($name, $value, false);
    }

    /**
     * Highlight a block of HTML containing defined blocks. Converts blocks from plain text
     * into highlighted code.
     *
     * @param string $htmlString
     * @return void
     */
    public function highlight(string $htmlString)
    {
        $tags = implode('|', $this->_config['validContainers']);
        $pattern = '#(<(' . $tags . ')[^>]' . $this->_config['langAttribute'] . '=["\']+([^\'".]*)["\']+>)(.*?)(</\2\s*>|$)#s';
        /*
         matches[0] = whole string
         matches[1] = open tag including lang attribute
         matches[2] = tag name
         matches[3] = value of lang attribute
         matches[4] = text to be highlighted
         matches[5] = end tag
        */
        return preg_replace_callback($pattern, [$this, '_processCodeBlock'], $htmlString);
    }

    /**
     * Highlight all the provided text as a given language.
     *
     * @param string $text The text to highight.
     * @param string $language The language to highlight as.
     * @param bool $withStylesheet If true will include GeSHi's generated stylesheet.
     * @return string Highlighted HTML.
     */
    public function highlightText(string $text, string $language, bool $withStylesheet = false)
    {
        $this->_getGeshi();
        $this->_geshi->set_source($text);
        $this->_geshi->set_language($language);

        return !$withStylesheet ?
            $this->_geshi->parse_code() :
            $this->_includeStylesheet() . $this->_geshi->parse_code();
    }

    /**
     * Highlight all the provided text as a given language.
     * Formats the results into an HTML table. This makes handling wide blocks
     * of code in a narrow page/space possible.
     *
     * @param string $text The text to highight.
     * @param string $language The language to highlight as.
     * @return string Highlighted HTML.
     */
    public function highlightAsTable($text, $language)
    {
        $this->_getGeshi();
        $this->_geshi->set_source($text);
        $this->_geshi->set_language($language);
        $this->_geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        $highlight = $this->_geshi->parse_code();

        return $this->_convertToTable($highlight);
    }

    protected function _convertToTable($highlight)
    {
        preg_match_all(
            '#<li\s*class\="li\d">(.*)</li>#',
            $highlight,
            $lines,
            PREG_SET_ORDER
        );
        $numbers = $code = [];
        foreach ($lines as $i => $line) {
            $numbers[] = sprintf('<div class="de1">%d</div>', $i + 1);
            $code[] = $line[1];
        }
        $template = <<<HTML
<table class="code" cellspacing="0" cellpadding="0">
<tbody>
	<tr><td class="code-numbers">%s</td>
	<td class="code-block">%s</td></tr>
</tbody>
</table>
HTML;

        return sprintf(
            $template,
            implode("\n", $numbers),
            implode("\n", $code)
        );
    }

    /**
     * Get the instance of GeSHI used by the helper.
     */
    protected function _getGeshi()
    {
        if (!$this->_geshi) {
            $this->_geshi = new GeSHi();
        }
        $this->_configureInstance($this->_geshi);

        return $this->_geshi;
    }

    /**
     * Preg Replace Callback
     * Uses matches made earlier runs geshi returns processed code blocks.
     *
     * @param array $matches code block groups
     * @return string Completed replacement string
     */
    protected function _processCodeBlock(array $matches)
    {
        [$block, $openTag, $tagName, $lang, $code, $closeTag] = $matches;
        unset($matches);

        // check language
        $lang = $this->validLang($lang);
        $code = html_entity_decode($code, ENT_QUOTES); // decode text in code block as GeSHi will re-encode it.

        if (isset($this->_config['containerMap'][$tagName])) {
            $patt = '/' . preg_quote($tagName) . '/';
            $openTag = preg_replace($patt, $this->_config['containerMap'][$tagName][0], $openTag);
            $closeTag = preg_replace($patt, $this->_config['containerMap'][$tagName][1], $closeTag);
        }

        if ($this->_config['showPlainTextButton']) {
            $button = '<a href="#null" class="geshi-plain-text">Show Plain Text</a>';
            $openTag = $button . $openTag;
        }

        if ($lang) {
            $highlighted = $this->highlightText(trim($code), $lang);

            return $openTag . $highlighted . $closeTag;
        }

        return $openTag . $code . $closeTag;
    }

    /**
     * Check if the current language is a valid language.
     *
     * @param string $lang Language
     * @return string|null
     */
    public function validLang(string $lang)
    {
        if (in_array($lang, $this->_config['validLanguages'])) {
            return $lang;
        }
        if ($this->_config['defaultLanguage']) {
            return $this->_config['defaultLanguage'];
        }

        return null;
    }

    /**
     * Configure a geshi Instance the way we want it.
     *
     *     $this->Geshi->features = array(...)
     *
     * @param \GeSHi $geshi Geshi instance
     * @return void
     */
    protected function _configureInstance(GeSHi $geshi)
    {
        if (empty($this->_config['features'])) {
            if (empty($this->_config['configPath'])) {
                $this->_config['configPath'] = ROOT . DS . 'config' . DS;
            }
            if (file_exists($this->_config['configPath'] . 'geshi.php')) {
                include $this->_config['configPath'] . 'geshi.php';
            }

            return;
        }
        foreach ($this->_config['features'] as $key => $value) {
            foreach ($value as &$test) {
                if (is_string($test) && defined($test)) {
                    // convert strings to Geshi's constant values
                    // (exists possibility of name collisions)
                    $test = constant($test);
                }
            }
            unset($test);
            call_user_func_array([$geshi, $key], $value);
        }
    }

    /**
     * Include the GeSHi-generated inline stylesheet.
     *
     * @return string
     */
    protected function _includeStylesheet()
    {
        $template = <<<HTML
\n<style type="text/css">
<!--
%s
-->
</style>\n
HTML;

        return sprintf(
            $template,
            $this->_geshi->get_stylesheet()
        );
    }
}
