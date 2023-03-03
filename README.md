## Geshi Helper Plugin

This plugin provides a simple helper for adding GeSHi syntax highlighting to
your application.

## Installation

You can install the plugin with composer. Add the following your composer.json file:

```json
"require": {
	"markstory/geshi": "~3.0"
}
```

After running `composer update` you should also remember to load the plugin.
In your application's bootstrap.php file add the following:

```php
Plugin::load('Geshi');
```

### Include the helper in your controller

The GeSHi helper offers three different ways to set GeSHi's features, which
affects how you will include the helper in your controller.

If you want to use a `geshi.php` configuration file, or you want to set the
features from within your view later, make sure to include without
pre-configuration. Otherwise if you want to include the features' settings
in your helper declaration, include with pre-configuration, both explained
next.

### Include without pre-configuration

To use the helper, include the helper in your View:

```php
public $helpers = ['Geshi.Geshi'];
```

You will have to include a `geshi.php` configuration file or simply set the
features later from your view.

### Include with pre-configuration

To use the helper and specify specific GeSHi features, use the options form
of including helpers:

```php
public $helpers = [
	'Geshi.Geshi' => [
		'set_header_type' => ['GESHI_FANCY_LINE_NUMBERS', 5]
	]
];
```

Where the passed-in key is a GeSHi function name, and the passed-in values
is an array of values for the function. Note that because GeSHi's own
constants are out of scope at this point, you must quote named constants as
shown above. GeshiHelper will resolve them for you.

### Methods

**highlight($html)**

This method will scan HTML for `<pre>` blocks with a known lang attribute. This
method is good for highlighting code samples in blog posts or wiki pages.

You can indicate the language of the text in a `<pre>` block by setting the lang
attribute.  For example:

```html
<pre lang="php">
<?php
echo 'hi'
</pre>
```

Will be highlighted as php code.

**highlightText($text, $language)**

This method will highlight `$text` in `$language`.  Use this method to
highlight text in any language GeSHI supports.

**highlightText($text, $language, $withStylesheet)**

This is the same method as above, but if you specify true for the optional,
third parameter then the helper will include the GeSHi-generated inline style
sheet.

### Configuration

As mentioned earlier there are three ways to set GeSHI's feature options.

## Configure in your controller

Above "Include with pre-configuration" details how to do this. If you pre-configure
in your controller, it's still possible to override these initial settings by
configuring in your views.

## Configure in your views

You can configure GeSHI's features from within your views
by accessing the `$features` variable so: `$this->Geshi->features = array(...)`, for example:

```php
$this->Geshi->setConfig('features', [
	'geshi_function_name' => ['geshi_parameter', list, values]
]);
```

Note that GeSHI's constants _are_ in scope here, and so quoting parameter
values is optional from within views.

Setting the features in your views gives you great flexibility to use GeSHi with (for example)
multiple languages served by the same controller.

## Configure via a configuration file

You can configure the GeSHi instance by creating a `geshi.php` file in your
`/config` directory.  This file should contain the code/method calls to
configure the `$geshi` object.  An example can be found in
`tests/geshi.php`

Note that the configuration file will be ignored completely if you have pre-configured
the GeSHi instance in your `$helpers` or have set `$this->Geshi->features` in your view.

## License

This code is licensed under the MIT License.
