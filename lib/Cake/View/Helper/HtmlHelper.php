<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.9.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppHelper', 'View/Helper');
App::uses('CakeResponse', 'Network');

/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html
 */
class HtmlHelper extends AppHelper
{
	/**
	 * Reference to the Response object
	 *
	 * @var CakeResponse
	 */
	public $response;

	/**
	 * html tags used by this helper.
	 *
	 * @var array
	 */
	protected $_tags = array(
		'meta' => '<meta%s/>',
		'metalink' => '<link href="%s"%s/>',
		'link' => '<a href="%s"%s>%s</a>',
		'form' => '<form action="%s"%s>',
		'formend' => '</form>',
		'input' => '<input name="%s"%s/>',
		'textarea' => '<textarea name="%s"%s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s"%s/>',
		'checkbox' => '<input type="checkbox" name="%s" %s/>',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]"%s />',
		'radio' => '<input type="radio" name="%s" id="%s"%s />%s',
		'selectstart' => '<select name="%s"%s>',
		'selectmultiplestart' => '<select name="%s[]"%s>',
		'selectempty' => '<option value=""%s>&nbsp;</option>',
		'selectoption' => '<option value="%s"%s>%s</option>',
		'selectend' => '</select>',
		'password' => '<input type="password" name="%s" %s/>',
		'file' => '<input type="file" name="%s" %s/>',
		'file_no_model' => '<input type="file" name="%s" %s/>',
		'submit' => '<input %s/>',
		'button' => '<button%s>%s</button>',
		'image' => '<img src="%s" %s/>',
		'label' => '<label for="%s"%s>%s</label>',
		'css' => '<link rel="%s" type="text/css" href="%s" %s/>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'error' => '<div%s>%s</div>',
		'javascriptlink' => '<script type="text/javascript" src="%s"%s></script>'
	);

	/**
	 * Breadcrumbs.
	 *
	 * @var array
	 */
	protected $_crumbs = array();

	/**
	 * Names of script files that have been included once
	 *
	 * @var array
	 */
	protected $_includedScripts = array();

	/**
	 * Options for the currently opened script block buffer if any.
	 *
	 * @var array
	 */
	protected $_scriptBlockOptions = array();

	/**
	 * Document type definitions
	 *
	 * @var array
	 */
	protected $_docTypes = array(
		'html5' => '<!DOCTYPE html>',
	);

	/**
	 * Constructor
	 *
	 * ### Settings
	 *
	 * - `configFile` A file containing an array of tags you wish to redefine.
	 *
	 * ### Customizing tag sets
	 *
	 * Using the `configFile` option you can redefine the tag HtmlHelper will use.
	 * The file named should be compatible with HtmlHelper::loadConfig().
	 *
	 * @param View $View The View this helper is being attached to.
	 * @param array $settings Configuration settings for the helper.
	 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		if (is_object($this->_View->response)) {
			$this->response = $this->_View->response;
		} else {
			$this->response = new CakeResponse();
		}
		if (!empty($settings['configFile'])) {
			$this->loadConfig($settings['configFile']);
		}
	}

	/**
	 * Adds a link to the breadcrumbs array.
	 *
	 * @param string $name Text for link
	 * @param string $link URL for link (if empty it won't be a link)
	 * @param string|array $options Link attributes e.g. array('id' => 'selected')
	 * @return void
	 * @see HtmlHelper::link() for details on $options that can be used.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
	 */
	public function addCrumb($name, $link = null, $options = null) {
		$this->_crumbs[] = array($name, $link, $options);
	}

	/**
	 * Creates a link to an external resource and handles basic meta tags
	 *
	 * Create a meta tag that is output inline:
	 *
	 * `$this->Html->meta('icon', 'favicon.ico');
	 *
	 * Append the meta tag to `$scripts_for_layout`:
	 *
	 * `$this->Html->meta('description', 'A great page', array('inline' => false));`
	 *
	 * Append the meta tag to custom view block:
	 *
	 * `$this->Html->meta('description', 'A great page', array('block' => 'metaTags'));`
	 *
	 * ### Options
	 *
	 * - `inline` Whether or not the link element should be output inline. Set to false to
	 *   have the meta tag included in `$scripts_for_layout`, and appended to the 'meta' view block.
	 * - `block` Choose a custom block to append the meta tag to. Using this option
	 *   will override the inline option.
	 *
	 * @param string $type The title of the external resource
	 * @param string|array $url The address of the external resource or string for content attribute
	 * @param array $options Other attributes for the generated tag. If the type attribute is html,
	 *    rss, atom, or icon, the mime-type is returned.
	 * @return string A completed `<link />` element.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::meta
	 */
	public function meta($type, $url = null, $options = array()) {
		$options += array('inline' => true, 'block' => null);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (!is_array($type)) {
			$types = array(
				'rss' => array('type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $url),
				'atom' => array('type' => 'application/atom+xml', 'title' => $type, 'link' => $url),
				'icon' => array('type' => 'image/x-icon', 'rel' => 'icon', 'link' => $url),
				'keywords' => array('name' => 'keywords', 'content' => $url),
				'description' => array('name' => 'description', 'content' => $url),
			);

			if ($type === 'icon' && $url === null) {
				$types['icon']['link'] = $this->webroot('favicon.ico');
			}

			if (isset($types[$type])) {
				$type = $types[$type];
			} elseif (!isset($options['type']) && $url !== null) {
				if (is_array($url) && isset($url['ext'])) {
					$type = $types[$url['ext']];
				} else {
					$type = $types['rss'];
				}
			} elseif (isset($options['type']) && isset($types[$options['type']])) {
				$type = $types[$options['type']];
				unset($options['type']);
			} else {
				$type = array();
			}
		}

		$options = array_merge($type, $options);
		$out = null;

		if (isset($options['link'])) {
			if (isset($options['rel']) && $options['rel'] === 'icon') {
				$out = sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('block', 'link'), ' ', ' '));
				$options['rel'] = 'shortcut icon';
			} else {
				$options['link'] = $this->url($options['link'], true);
			}
			$out .= sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('block', 'link'), ' ', ' '));
		} else {
			$out = sprintf($this->_tags['meta'], $this->_parseAttributes($options, array('block', 'type'), ' ', ' '));
		}

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
	}

	/**
	 * Returns a charset META-tag.
	 *
	 * @param string $charset The character set to be used in the meta tag. If empty,
	 *  The App.encoding value will be used. Example: "utf-8".
	 * @return string A meta tag containing the specified character set.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::charset
	 */
	public function charset($charset = null) {
		if (empty($charset)) {
			$charset = strtolower(Configure::read('App.encoding'));
		}
		return sprintf($this->_tags['charset'], (!empty($charset) ? $charset : 'utf-8'));
	}

	/**
	 * Creates an HTML link.
	 *
	 * If $url starts with "http://" this is treated as an external link. Else,
	 * it is treated as a path to controller/action and parsed with the
	 * HtmlHelper::url() method.
	 *
	 * If the $url is empty, $title is used instead.
	 *
	 * ### Options
	 *
	 * - `escape` Set to false to disable escaping of title and attributes.
	 * - `confirm` JavaScript confirmation message.
	 *
	 * @param string $title The content to be wrapped by <a> tags.
	 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
	 * @param array $options Array of HTML attributes.
	 * @param string $confirmMessage JavaScript confirmation message.
	 * @return string An `<a />` element.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::link
	 */
	public function link($title, $url = null, $options = array(), $confirmMessage = false) {
		$escapeTitle = true;
		if ($url !== null) {
			$url = $this->url($url);
		} else {
			$url = $this->url($title);
			$title = htmlspecialchars_decode($url, ENT_QUOTES);
			$title = h(urldecode($title));
			$escapeTitle = false;
		}

		if (isset($options['escape'])) {
			$escapeTitle = $options['escape'];
		}

		if ($escapeTitle === true) {
			$title = h($title);
		} elseif (is_string($escapeTitle)) {
			$title = htmlentities($title, ENT_QUOTES, $escapeTitle);
		}

		if (!empty($options['confirm'])) {
			$confirmMessage = $options['confirm'];
			unset($options['confirm']);
		}
		if ($confirmMessage) {
			$confirmMessage = str_replace("'", "\'", $confirmMessage);
			$confirmMessage = str_replace('"', '\"', $confirmMessage);
			$options['onclick'] = "return confirm('{$confirmMessage}');";
		} elseif (isset($options['default']) && !$options['default']) {
			if (isset($options['onclick'])) {
				$options['onclick'] .= ' event.returnValue = false; return false;';
			} else {
				$options['onclick'] = 'event.returnValue = false; return false;';
			}
			unset($options['default']);
		}
		return sprintf($this->_tags['link'], $url, $this->_parseAttributes($options), $title);
	}

	/**
	 * Creates a link element for CSS stylesheets.
	 *
	 * ### Usage
	 *
	 * Include one CSS file:
	 *
	 * `echo $this->Html->css('styles.css');`
	 *
	 * Include multiple CSS files:
	 *
	 * `echo $this->Html->css(array('one.css', 'two.css'));`
	 *
	 * Add the stylesheet to the `$scripts_for_layout` layout var:
	 *
	 * `$this->Html->css('styles.css', null, array('inline' => false));`
	 *
	 * Add the stylesheet to a custom block:
	 *
	 * `$this->Html->css('styles.css', null, array('block' => 'layoutCss'));`
	 *
	 * ### Options
	 *
	 * - `inline` If set to false, the generated tag will be appended to the 'css' block,
	 *   and included in the `$scripts_for_layout` layout variable. Defaults to true.
	 * - `block` Set the name of the block link/style tag will be appended to. This overrides the `inline`
	 *   option.
	 * - `plugin` False value will prevent parsing path as a plugin
	 * - `fullBase` If true the url will get a full address for the css file.
	 *
	 * @param string|array $path The name of a CSS style sheet or an array containing names of
	 *   CSS stylesheets. If `$path` is prefixed with '/', the path will be relative to the webroot
	 *   of your application. Otherwise, the path will be relative to your CSS path, usually webroot/css.
	 * @param string $rel Rel attribute. Defaults to "stylesheet". If equal to 'import' the stylesheet will be imported.
	 * @param array $options Array of HTML attributes.
	 * @return string CSS <link /> or <style /> tag, depending on the type of link.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::css
	 */
	public function css($path, $rel = null, $options = array()) {
		$options += array('block' => null, 'inline' => true);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $rel, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '//') !== false) {
			$url = $path;
		} else {
			$url = $this->assetUrl($path, $options + array('pathPrefix' => CSS_URL, 'ext' => '.css'));
			$options = array_diff_key($options, array('fullBase' => null));

			if (Configure::read('Asset.filter.css')) {
				$pos = strpos($url, CSS_URL);
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'ccss/' . substr($url, $pos + strlen(CSS_URL));
				}
			}
		}

		if ($rel === 'import') {
			$out = sprintf($this->_tags['style'], $this->_parseAttributes($options, array('inline', 'block'), '', ' '), '@import url(' . $url . ');');
		} else {
			if (!$rel) {
				$rel = 'stylesheet';
			}
			$out = sprintf($this->_tags['css'], $rel, $url, $this->_parseAttributes($options, array('inline', 'block'), '', ' '));
		}

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
	}

	/**
	 * Returns one or many `<script>` tags depending on the number of scripts given.
	 *
	 * If the filename is prefixed with "/", the path will be relative to the base path of your
	 * application. Otherwise, the path will be relative to your JavaScript path, usually webroot/js.
	 *
	 *
	 * ### Usage
	 *
	 * Include one script file:
	 *
	 * `echo $this->Html->script('styles.js');`
	 *
	 * Include multiple script files:
	 *
	 * `echo $this->Html->script(array('one.js', 'two.js'));`
	 *
	 * Add the script file to the `$scripts_for_layout` layout var:
	 *
	 * `$this->Html->script('styles.js', array('inline' => false));`
	 *
	 * Add the script file to a custom block:
	 *
	 * `$this->Html->script('styles.js', null, array('block' => 'bodyScript'));`
	 *
	 * ### Options
	 *
	 * - `inline` Whether script should be output inline or into `$scripts_for_layout`. When set to false,
	 *   the script tag will be appended to the 'script' view block as well as `$scripts_for_layout`.
	 * - `block` The name of the block you want the script appended to. Leave undefined to output inline.
	 *   Using this option will override the inline option.
	 * - `once` Whether or not the script should be checked for uniqueness. If true scripts will only be
	 *   included once, use false to allow the same script to be included more than once per request.
	 * - `plugin` False value will prevent parsing path as a plugin
	 * - `fullBase` If true the url will get a full address for the script file.
	 *
	 * @param string|array $url String or array of javascript files to include
	 * @param array|boolean $options Array of options, and html attributes see above. If boolean sets $options['inline'] = value
	 * @return mixed String of `<script />` tags or null if $inline is false or if $once is true and the file has been
	 *   included before.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::script
	 */
	public function script($url, $options = array()) {
		if (is_bool($options)) {
			list($inline, $options) = array($options, array());
			$options['inline'] = $inline;
		}
		$options = array_merge(array('block' => null, 'inline' => true, 'once' => true), $options);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (is_array($url)) {
			$out = '';
			foreach ($url as $i) {
				$out .= "\n\t" . $this->script($i, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return null;
		}
		if ($options['once'] && isset($this->_includedScripts[$url])) {
			return null;
		}
		$this->_includedScripts[$url] = true;

		if (strpos($url, '//') === false) {
			$url = $this->assetUrl($url, $options + array('pathPrefix' => JS_URL, 'ext' => '.js'));
			$options = array_diff_key($options, array('fullBase' => null));

			if (Configure::read('Asset.filter.js')) {
				$url = str_replace(JS_URL, 'cjs/', $url);
			}
		}
		$attributes = $this->_parseAttributes($options, array('block', 'once'), ' ');
		$out = sprintf($this->_tags['javascriptlink'], $url, $attributes);

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
	}

	/**
	 * Wrap $script in a script tag.
	 *
	 * ### Options
	 *
	 * - `safe` (boolean) Whether or not the $script should be wrapped in <![CDATA[ ]]>
	 * - `inline` (boolean) Whether or not the $script should be added to
	 *   `$scripts_for_layout` / `script` block, or output inline. (Deprecated, use `block` instead)
	 * - `block` Which block you want this script block appended to.
	 *   Defaults to `script`.
	 *
	 * @param string $script The script to wrap
	 * @param array $options The options to use. Options not listed above will be
	 *    treated as HTML attributes.
	 * @return mixed string or null depending on the value of `$options['block']`
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptBlock
	 */
	public function scriptBlock($script, $options = array()) {
		$options += array('type' => 'text/javascript', 'safe' => true, 'inline' => true);
		if ($options['safe']) {
			$script = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = 'script';
		}
		unset($options['inline'], $options['safe']);

		$attributes = $this->_parseAttributes($options, array('block'), ' ');
		$out = sprintf($this->_tags['javascriptblock'], $attributes, $script);

		if (empty($options['block'])) {
			return $out;
		} else {
			$this->_View->append($options['block'], $out);
		}
	}

	/**
	 * Builds CSS style data from an array of CSS properties
	 *
	 * ### Usage:
	 *
	 * {{{
	 * echo $this->Html->style(array('margin' => '10px', 'padding' => '10px'), true);
	 *
	 * // creates
	 * 'margin:10px;padding:10px;'
	 * }}}
	 *
	 * @param array $data Style data array, keys will be used as property names, values as property values.
	 * @param boolean $oneline Whether or not the style block should be displayed on one line.
	 * @return string CSS styling data
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::style
	 */
	public function style($data, $oneline = true) {
		if (!is_array($data)) {
			return $data;
		}
		$out = array();
		foreach ($data as $key => $value) {
			$out[] = $key . ':' . $value . ';';
		}
		if ($oneline) {
			return implode(' ', $out);
		}
		return implode("\n", $out);
	}

	/**
	 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
	 *
	 * If `$startText` is an array, the accepted keys are:
	 *
	 * - `text` Define the text/content for the link.
	 * - `url` Define the target of the created link.
	 *
	 * All other keys will be passed to HtmlHelper::link() as the `$options` parameter.
	 *
	 * @param string $separator Text to separate crumbs.
	 * @param string|array|boolean $startText This will be the first crumb, if false it defaults to first crumb in array. Can
	 *   also be an array, see above for details.
	 * @return string Composed bread crumbs
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
	 */
	public function getCrumbs($separator = '&raquo;', $startText = false) {
		$crumbs = $this->_prepareCrumbs($startText);
		if (!empty($crumbs)) {
			$out = array();
			foreach ($crumbs as $crumb) {
				if (!empty($crumb[1])) {
					$out[] = $this->link($crumb[0], $crumb[1], $crumb[2]);
				} else {
					$out[] = $crumb[0];
				}
			}
			return implode($separator, $out);
		} else {
			return null;
		}
	}

	/**
	 * Returns breadcrumbs as a (x)html list
	 *
	 * This method uses HtmlHelper::tag() to generate list and its elements. Works
	 * similar to HtmlHelper::getCrumbs(), so it uses options which every
	 * crumb was added with.
	 *
	 * ### Options
	 * - `separator` Separator content to insert in between breadcrumbs, defaults to ''
	 * - `firstClass` Class for wrapper tag on the first breadcrumb, defaults to 'first'
	 * - `lastClass` Class for wrapper tag on current active page, defaults to 'last'
	 *
	 * @param array $options Array of html attributes to apply to the generated list elements.
	 * @param string|array|boolean $startText This will be the first crumb, if false it defaults to first crumb in array. Can
	 *   also be an array, see `HtmlHelper::getCrumbs` for details.
	 * @return string breadcrumbs html list
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
	 */
	public function getCrumbList($options = array(), $startText = false) {
		$defaults = array('firstClass' => 'first', 'lastClass' => 'last', 'separator' => '');
		$options = array_merge($defaults, (array)$options);
		$firstClass = $options['firstClass'];
		$lastClass = $options['lastClass'];
		$separator = $options['separator'];
		unset($options['firstClass'], $options['lastClass'], $options['separator']);

		$crumbs = $this->_prepareCrumbs($startText);
		if (empty($crumbs)) {
			return null;
		}

		$result = '';
		$crumbCount = count($crumbs);
		$ulOptions = $options;
		foreach ($crumbs as $which => $crumb) {
			$options = array();
			if (empty($crumb[1])) {
				$elementContent = $crumb[0];
			} else {
				$elementContent = $this->link($crumb[0], $crumb[1], $crumb[2]);
			}
			if (!$which && $firstClass !== false) {
				$options['class'] = $firstClass;
			} elseif ($which == $crumbCount - 1 && $lastClass !== false) {
				$options['class'] = $lastClass;
			}
			if (!empty($separator) && ($crumbCount - $which >= 2)) {
				$elementContent .= $separator;
			}
			$result .= $this->tag('li', $elementContent, $options);
		}
		return $this->tag('ul', $result, $ulOptions);
	}

	/**
	 * Prepends startText to crumbs array if set
	 *
	 * @param string $startText Text to prepend
	 * @return array Crumb list including startText (if provided)
	 */
	protected function _prepareCrumbs($startText) {
		$crumbs = $this->_crumbs;
		if ($startText) {
			if (!is_array($startText)) {
				$startText = array(
					'url' => '/',
					'text' => $startText
				);
			}
			$startText += array('url' => '/', 'text' => __d('cake', 'Home'));
			list($url, $text) = array($startText['url'], $startText['text']);
			unset($startText['url'], $startText['text']);
			array_unshift($crumbs, array($text, $url, $startText));
		}
		return $crumbs;
	}

	/**
	 * Creates a formatted IMG element.
	 *
	 * This method will set an empty alt attribute if one is not supplied.
	 *
	 * ### Usage:
	 *
	 * Create a regular image:
	 *
	 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP'));`
	 *
	 * Create an image link:
	 *
	 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP', 'url' => 'http://cakephp.org'));`
	 *
	 * ### Options:
	 *
	 * - `url` If provided an image link will be generated and the link will point at
	 *   `$options['url']`.
	 * - `fullBase` If true the src attribute will get a full address for the image file.
	 * - `plugin` False value will prevent parsing path as a plugin
	 *
	 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
	 * @param array $options Array of HTML attributes. See above for special options.
	 * @return string completed img tag
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::image
	 */
	public function image($path, $options = array()) {
		$path = $this->assetUrl($path, $options + array('pathPrefix' => IMAGES_URL));
		$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));

		if (!isset($options['alt'])) {
			$options['alt'] = '';
		}

		$url = false;
		if (!empty($options['url'])) {
			$url = $options['url'];
			unset($options['url']);
		}

		$image = sprintf($this->_tags['image'], $path, $this->_parseAttributes($options, null, '', ' '));

		if ($url) {
			return sprintf($this->_tags['link'], $this->url($url), null, $image);
		}
		return $image;
	}

	/**
	 * Returns a formatted block tag, i.e DIV, SPAN, P.
	 *
	 * ### Options
	 *
	 * - `escape` Whether or not the contents should be html_entity escaped.
	 *
	 * @param string $name Tag name.
	 * @param string $text String content that will appear inside the div element.
	 *   If null, only a start tag will be printed
	 * @param array $options Additional HTML attributes of the DIV tag, see above.
	 * @return string The formatted tag element
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::tag
	 */
	public function tag($name, $text = null, $options = array()) {
		if (empty($name)) {
			return $text;
		}
		if (is_array($options) && isset($options['escape']) && $options['escape']) {
			$text = h($text);
			unset($options['escape']);
		}
		if (!is_array($options)) {
			$options = array('class' => $options);
		}
		if ($text === null) {
			$tag = 'tagstart';
		} else {
			$tag = 'tag';
		}
		return sprintf($this->_tags[$tag], $name, $this->_parseAttributes($options, null, ' ', ''), $text, $name);
	}

	/**
	 * Returns a formatted existent block of $tags
	 *
	 * @param string $tag Tag name
	 * @return string Formatted block
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::useTag
	 */
	public function useTag($tag) {
		if (!isset($this->_tags[$tag])) {
			return '';
		}
		$args = func_get_args();
		array_shift($args);
		foreach ($args as &$arg) {
			if (is_array($arg)) {
				$arg = $this->_parseAttributes($arg, null, ' ', '');
			}
		}
		return vsprintf($this->_tags[$tag], $args);
	}

	/**
	 * Load Html tag configuration.
	 *
	 * Loads a file from APP/Config that contains tag data. By default the file is expected
	 * to be compatible with PhpReader:
	 *
	 * `$this->Html->loadConfig('tags.php');`
	 *
	 * tags.php could look like:
	 *
	 * {{{
	 * $tags = array(
	 *		'meta' => '<meta %s>'
	 * );
	 * }}}
	 *
	 * If you wish to store tag definitions in another format you can give an array
	 * containing the file name, and reader class name:
	 *
	 * `$this->Html->loadConfig(array('tags.ini', 'ini'));`
	 *
	 * Its expected that the `tags` index will exist from any configuration file that is read.
	 * You can also specify the path to read the configuration file from, if APP/Config is not
	 * where the file is.
	 *
	 * `$this->Html->loadConfig('tags.php', APP . 'Lib' . DS);`
	 *
	 * Configuration files can define the following sections:
	 *
	 * - `tags` The tags to replace.
	 * - `minimizedAttributes` The attributes that are represented like `disabled="disabled"`
	 * - `docTypes` Additional doctypes to use.
	 * - `attributeFormat` Format for long attributes e.g. `'%s="%s"'`
	 * - `minimizedAttributeFormat` Format for minimized attributes e.g. `'%s="%s"'`
	 *
	 * @param string|array $configFile String with the config file (load using PhpReader) or an array with file and reader name
	 * @param string $path Path with config file
	 * @return mixed False to error or loaded configs
	 * @throws ConfigureException
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#changing-the-tags-output-by-htmlhelper
	 */
	public function loadConfig($configFile, $path = null) {
		if (!$path) {
			$path = APP . 'Config' . DS;
		}
		$file = null;
		$reader = 'php';

		if (!is_array($configFile)) {
			$file = $configFile;
		} elseif (isset($configFile[0])) {
			$file = $configFile[0];
			if (isset($configFile[1])) {
				$reader = $configFile[1];
			}
		} else {
			throw new ConfigureException(__d('cake_dev', 'Cannot load the configuration file. Wrong "configFile" configuration.'));
		}

		$readerClass = Inflector::camelize($reader) . 'Reader';
		App::uses($readerClass, 'Configure');
		if (!class_exists($readerClass)) {
			throw new ConfigureException(__d('cake_dev', 'Cannot load the configuration file. Unknown reader.'));
		}

		$readerObj = new $readerClass($path);
		$configs = $readerObj->read($file);
		if (isset($configs['tags']) && is_array($configs['tags'])) {
			$this->_tags = array_merge($this->_tags, $configs['tags']);
		}
		if (isset($configs['minimizedAttributes']) && is_array($configs['minimizedAttributes'])) {
			$this->_minimizedAttributes = array_merge($this->_minimizedAttributes, $configs['minimizedAttributes']);
		}
		if (isset($configs['docTypes']) && is_array($configs['docTypes'])) {
			$this->_docTypes = array_merge($this->_docTypes, $configs['docTypes']);
		}
		if (isset($configs['attributeFormat'])) {
			$this->_attributeFormat = $configs['attributeFormat'];
		}
		if (isset($configs['minimizedAttributeFormat'])) {
			$this->_minimizedAttributeFormat = $configs['minimizedAttributeFormat'];
		}
		return $configs;
	}
}
