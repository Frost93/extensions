<?php
/**
 * @package         Components Anywhere
 * @version         3.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/tags.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/text.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';

RLFunctions::loadLanguage('plg_system_componentsanywhere');

/**
 * Plugin that places components
 */
class PlgSystemComponentsAnywhereHelper
{
	var $option      = '';
	var $params      = null;
	var $aid         = array();
	var $cache       = null;
	var $credentials = null;

	public function __construct(&$params)
	{
		$this->option = JFactory::getApplication()->input->get('option');

		$this->params                = $params;
		$this->params->comment_start = '<!-- START: Components Anywhere -->';
		$this->params->comment_end   = '<!-- END: Components Anywhere -->';
		$this->params->message_start = '<!--  Components Anywhere Message: ';
		$this->params->message_end   = ' -->';
		$this->params->protect_start = '<!-- START: CA_PROTECT -->';
		$this->params->protect_end   = '<!-- END: CA_PROTECT -->';

		$this->params->component_tag = trim($this->params->component_tag);
		$this->params->tag           = preg_quote($this->params->component_tag, '#');

		// Tag character start and end
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		// Break/paragraph start and end tags
		$this->params->breaks_start = RLTags::getRegexSurroundingTagPre();
		$this->params->breaks_end   = RLTags::getRegexSurroundingTagPost();
		$breaks_start               = $this->params->breaks_start;
		$breaks_end                 = $this->params->breaks_end;
		$inside_tag                 = RLTags::getRegexInsideTag();
		$spaces                     = RLTags::getRegexSpaces();

		$this->params->regex = '#'
			. '(?P<start_div>(?:'
			. $breaks_start
			. $tag_start . 'div(?: ' . $inside_tag . ')?' . $tag_end
			. $breaks_end
			. '\s*)?)'

			. '(?P<pre>' . $breaks_start . ')'
			. $tag_start . $this->params->tag . $spaces . '(?P<id>' . $inside_tag . ')' . $tag_end
			. '(?P<post>' . $breaks_end . ')'

			. '(?P<end_div>(?:\s*'
			. $breaks_start
			. $tag_start . '/div' . $tag_end
			. $breaks_end
			. ')?)'
			. '#s';

		$this->params->protected_tags = array(
			$this->params->tag_character_start . $this->params->component_tag,
		);

		$this->params->message = '';

		$this->aid = array_unique(JFactory::getUser()->getAuthorisedViewLevels());

		$this->cache = JFactory::getCache('plugin_componentsanywhere', 'output');
	}

	public function onContentPrepare(&$article, $context, $params)
	{

		$area    = isset($article->created_by) ? 'articles' : 'other';
		$context = (($params instanceof JRegistry) && $params->get('rl_search')) ? 'com_search.' . $params->get('readmore_limit') : $context;

		RLHelper::processArticle($article, $context, $this, 'processComponents', array($area, $context));
	}

	public function onAfterDispatch()
	{
		// only in html
		if (JFactory::getDocument()->getType() !== 'html' && !RLFunctions::isFeed())
		{
			return;
		}

		if (!$buffer = RLFunctions::getComponentBuffer())
		{
			return;
		}

		if (JFactory::getApplication()->input->get('rendercomponent'))
		{
			$doc          = JFactory::getDocument();
			$ret          = new stdClass;
			$ret->script  = $doc->_script;
			$ret->scripts = $doc->_scripts;
			$ret->style   = $doc->_style;
			$ret->styles  = $doc->_styleSheets;
			$ret->custom  = $doc->_custom;
			$ret->html    = $buffer;
			$ret->token   = JFactory::getSession()->getFormToken();

			if (count(JText::script()))
			{
				$lnEnd = $doc->_getLineEnd();
				$tab   = $doc->_getTab();

				// Adding it to javascript2 to force it into its own <script> block
				// This prevents some conflicts
				// The javascript2 will get replaced with javascript when adding to the doc
				if (!isset($ret->script['text/javascript2']))
				{
					$ret->script['text/javascript2'] = '';
				}
				$ret->script['text/javascript2'] .= $lnEnd
					. $tab . $tab . '(function() {' . $lnEnd
					. $tab . $tab . $tab . 'Joomla.JText.load(' . json_encode(JText::script()) . ');' . $lnEnd
					. $tab . $tab . '})();' . $lnEnd;
			}

			header('Content-Type: application/json');
			echo json_encode($ret);
			die();
		}

		$this->replaceTags($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html' && !RLFunctions::isFeed())
		{
			return;
		}

		$html = JFactory::getApplication()->getBody();
		if ($html == '')
		{
			return;
		}

		if (JFactory::getDocument()->getType() != 'html')
		{
			$this->replaceTags($html, 'body');
		}
		else
		{
			// only do stuff in body
			list($pre, $body, $post) = RLText::getBody($html);
			$this->replaceTags($body, 'body');

			if (strpos($pre, '</head>') !== false || strpos($body, '<!-- CA HEAD START') !== false)
			{
				preg_match_all('#<!-- CA HEAD START STYLES -->(.*?)<!-- CA HEAD END STYLES -->#s', $body, $matches, PREG_SET_ORDER);

				if (!empty($matches))
				{
					$styles = '';
					foreach ($matches as $match)
					{
						$styles .= $match['1'];
						$body = str_replace($match['0'], '', $body);
					}

					$add_before = '</head>';
					if (preg_match('#<link [^>]+templates/#', $body, $add_before_match))
					{
						$add_before = $add_before_match['0'];
					}

					$pre = str_replace($add_before, $styles . $add_before, $pre);
				}

				preg_match_all('#<!-- CA HEAD START SCRIPTS -->(.*?)<!-- CA HEAD END SCRIPTS -->#s', $body, $matches, PREG_SET_ORDER);

				if (!empty($matches))
				{
					$scripts = '';
					foreach ($matches as $match)
					{
						$scripts .= $match['1'];
						$body = str_replace($match['0'], '', $body);
					}

					$add_before = '</head>';
					if (preg_match('#<script [^>]+templates/#', $body, $add_before_match))
					{
						$add_before = $add_before_match['0'];
					}

					$pre = str_replace($add_before, $scripts . $add_before, $pre);
				}

				$this->removeDuplicatesFromHead($pre, '#<link[^>]*>#');
				$this->removeDuplicatesFromHead($pre, '#<style.*?</style>#');
				$this->removeDuplicatesFromHead($pre, '#<script.*?</script>#');
			}
			$html = $pre . $body . $post;
		}

		$this->cleanLeftoverJunk($html);

		JFactory::getApplication()->setBody($html);
	}

	function replaceTags(&$string, $area = 'article')
	{
		if (!is_string($string) || $string == '')
		{
			return;
		}

		if (strpos($string, $this->params->tag_character_start . $this->params->component_tag) === false)
		{
			return;
		}

		// allow in component?
		if (RLProtect::isRestrictedComponent(isset($this->params->components) ? $this->params->components : array(), $area))
		{

			$this->protect($string);

			$this->removeAll($string, $area);

			RLProtect::unprotect($string);

			return;
		}

		$this->protect($string);

		$this->params->message = '';

		// COMPONENT
		if (RLFunctions::isFeed())
		{
			$s      = '#(<item[^>]*>)#s';
			$string = preg_replace($s, '\1<!-- START: COMA_COMPONENT -->', $string);
			$string = str_replace('</item>', '<!-- END: COMA_COMPONENT --></item>', $string);
		}
		if (strpos($string, '<!-- START: COMA_COMPONENT -->') === false)
		{
			$this->tagArea($string, 'COMA', 'component');
		}

		$this->params->message = '';

		$components = $this->getTagArea($string, 'COMA', 'component');

		foreach ($components as $component)
		{
			$this->processComponents($component['1'], 'components');
			$string = str_replace($component['0'], $component['1'], $string);
		}

		// EVERYWHERE
		$this->processComponents($string, 'other');

		RLProtect::unprotect($string);
	}

	function tagArea(&$string, $ext = 'EXT', $area = '')
	{
		if ($string && $area)
		{
			$string = '<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->' . $string . '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			if ($area == 'article_text')
			{
				$string = preg_replace('#(<hr class="system-pagebreak".*?>)#si', '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->\1<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->', $string);
			}
		}
	}

	function getTagArea(&$string, $ext = 'EXT', $area = '')
	{
		$matches = array();
		if ($string && $area)
		{
			$start   = '<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			$end     = '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			$matches = explode($start, $string);
			array_shift($matches);
			foreach ($matches as $i => $match)
			{
				list($text) = explode($end, $match, 2);
				$matches[$i] = array(
					$start . $text . $end,
					$text,
				);
			}
		}

		return $matches;
	}

	public function removeAll(&$string, $area = 'articles')
	{
		$this->params->message = JText::_('CA_OUTPUT_REMOVED_NOT_ENABLED');
		$this->processComponents($string, $area);
	}

	function processComponents(&$string, $area = 'articles', $context = '')
	{
		// Check if tags are in the text snippet used for the search component
		if (strpos($context, 'com_search.') === 0)
		{
			$limit = explode('.', $context, 2);
			$limit = (int) array_pop($limit);

			$string_check = substr($string, 0, $limit);

			if (strpos($string_check, $this->params->tag_character_start . $this->params->tag) === false)
			{
				return;
			}
		}


		if (strpos($string, $this->params->tag_character_start . $this->params->component_tag) === false)
		{
			return;
		}

		self::replace($string, $this->params->regex, $area);
	}

	function replace(&$string, $regex, $area = 'articles')
	{
		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array(
				$this->params->tag_character_start . $this->params->component_tag,
			),
			null, 200, 500
		);

		if ($string == '')
		{
			$string = $pre_string . $string . $post_string;

			return;
		}

		if (@preg_match($regex . 'u', $string))
		{
			$regex .= 'u';
		}

		$matches = array();
		$count   = 0;

		$protects = array();
		while ($count++ < 10 && preg_match_all($regex, $string, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				if ($this->processMatch($string, $match, $area))
				{
					continue;
				}

				$protected  = $this->params->protect_start . base64_encode($match['0']) . $this->params->protect_end;
				$string     = str_replace($match['0'], $protected, $string);
				$protects[] = array($match['0'], $protected);
			}

			$matches = array();
		}
		foreach ($protects as $protect)
		{
			$string = str_replace($protect['1'], $protect['0'], $string);
		}

		$string = $pre_string . $string . $post_string;
	}

	function processMatch(&$string, &$data, $area = 'articles')
	{
		if (!empty($this->params->message))
		{
			$html = '';

			if ($this->params->place_comments)
			{
				$html = $this->params->message_start . $this->params->message . $this->params->message_end;
			}

			$string = str_replace($data['0'], $html, $string);

			return true;
		}

		$id = trim($data['id']);

		// Handle multiple attribute syntaxes
		$id  = str_replace(
			array('|cache', '|forceitemid', '|keepurl', '|keepurlss'),
			array('|caching', '|force_itemid', '|keepurls', '|keepurls'),
			$id
		);
		$tag = RLTags::getTagValues($id, array('url'));

		foreach ($tag->params as $param)
		{
			$tag->{$param} = 1;
		}
		unset($tag->params);

		$tag->force_itemid = isset($tag->force_itemid) ? $tag->force_itemid : $this->params->force_itemid;
		$tag->keepurls     = isset($tag->keepurls) ? $tag->keepurls : $this->params->keepurls;
		$tag->caching      = isset($tag->caching) ? $tag->caching : $this->params->caching;

		$html = $this->processComponent($tag, $area);

		list($start_div, $end_div) = $this->getDivTags($data);

		$tags = RLTags::cleanSurroundingTags(array(
			'start_div_pre'  => $start_div['pre'],
			'start_div_post' => $start_div['post'],
			'pre'            => $data['pre'],
			'post'           => $data['post'],
			'end_div_pre'    => $end_div['pre'],
			'end_div_post'   => $end_div['post'],
		));

		$html = $tags['start_div_pre'] . $start_div['tag'] . $tags['start_div_post']
			. $tags['pre'] . $html . $tags['post']
			. $tags['end_div_pre'] . $end_div['tag'] . $tags['end_div_post'];

		if ($this->params->place_comments)
		{
			$html = $this->params->comment_start . $html . $this->params->comment_end;
		}

		$string = str_replace($data['0'], $html, $string);
		unset($data);

		return true;
	}

	function processComponent($tag, $area = '')
	{
		$url = ltrim(RLText::html_entity_decoder(trim($tag->url)), '/');

		$pagination_stuff = array('p', 'page', 'limitstart', 'start', 'filter', 'filter-search');
		$full_url         = $url;
		foreach ($pagination_stuff as $key)
		{
			if (!isset($_GET[$key]))
			{
				continue;
			}

			$full_url .= (strpos($url, '?') === false) ? '?' : '&';
			$full_url .= $key . '=' . $_GET[$key];
		}

		if ($tag->force_itemid)
		{
			$full_url = preg_replace(
				'#((?:\?|&(?:amp;)?)Itemid)=[0-9]+#',
				'\1=' . JFactory::getApplication()->input->get('Itemid'),
				$full_url
			);

			$full_url .= (strpos($full_url, '?') === false) ? '?' : '&';
			$full_url .= 'Itemid=' . JFactory::getApplication()->input->get('Itemid');
		}

		$data = $this->getByURL($full_url, $tag->caching);

		if (!$data)
		{
			if ($this->params->place_comments)
			{
				return $this->params->message_start . JText::_('CA_OUTPUT_REMOVED_INVALID') . $this->params->message_end;
			}

			return '';
		}

		$this->addScriptsAndStyles($data, $area);

		$uri = JUri::getInstance();

		// Remove tmpl and rendercomponent parameters that have possibly been added to urls by the component
		$this->removeFromUrls($data->html, '(?:tmpl=component&(?:amp;)?)?rendercomponent=1');

		if ($this->params->force_remove_tmpl)
		{
			$this->removeFromUrls($data->html, 'tmpl=component');
		}

		// Replace the form token with the current correct one
		$data->html = str_replace(
			$data->token,
			JFactory::getSession()->getFormToken(),
			$data->html
		);

		// Replace the return values in urls
		$data->html = preg_replace(
			'#(\?|&(?:amp;)?)return=(?:[a-z0-9=]+)#i',
			'\1return=' . base64_encode($uri->toString()),
			$data->html
		);

		if ($tag->force_itemid)
		{
			// Replace Itemid
			$data->html = preg_replace(
				'#(\?|&(?:amp;)?)Itemid=[0-9]+#s',
				'\1Itemid=' . JFactory::getApplication()->input->get('Itemid'),
				$data->html
			);
		}

		if (!$tag->keepurls)
		{
			$path = $uri->getPath();
			$path .= (strpos($path, '?') === false) ? '?' : '&';

			// Replace urls
			$data->html = str_replace(
				array(
					$url,
					JRoute::_($url),
					JRoute::_($url, 0),
				),
				$path,
				$data->html
			);
			// Also get non-sef matches
			$url_regex  = str_replace('&', '&(?:amp;)?', str_replace('index.php', '', $url));
			$data->html = preg_replace('#"[^"]+' . $url_regex . '("|&)#si', '"' . $path . '\1', $data->html);
		}

		return $data->html;
	}

	private function removeFromUrls(&$html, $search = '')
	{
		// Replace the <search term>&<something else> cases
		$html = preg_replace('#(\?|&(?:amp;)?)' . $search . '&(?:amp;)?#si', '\1', $html);
		// Replace the <search term> cases
		$html = preg_replace('#(?:\?|&(?:amp;)?)' . $search . '#si', '', $html);
	}

	function addScriptsAndStyles(&$data, $area = '')
	{
		// add set scripts and styles to current jdoc
		$doc = JFactory::getDocument();
		$this->removeDuplicatesFromObject($data->styles, $doc->_styleSheets);
		$this->removeDuplicatesFromObject($data->style, $doc->_style, 1);
		$this->removeDuplicatesFromObject($data->scripts, $doc->_scripts);
		$this->removeDuplicatesFromObject($data->script, $doc->_script, 1);

		if ($area == 'articles')
		{
			foreach ($data->styles as $style => $attr)
			{
				$doc->addStyleSheet($style, $attr->mime, $attr->media, $attr->attribs);
			}
			foreach ($data->style as $type => $content)
			{
				$doc->addStyleDeclaration($content, $type);
			}
			foreach ($data->scripts as $script => $attr)
			{
				$doc->addScript($script, $attr->mime, $attr->defer, $attr->async);
			}
			foreach ($data->script as $type => $content)
			{
				$doc->addScriptDeclaration($content, str_replace('javascript2', 'javascript', $type));
			}
			foreach ($data->custom as $content)
			{
				$doc->addCustomTag($content);
			}

			return;
		}

		$inline_head_styles  = array();
		$inline_head_scripts = array();

		// Generate stylesheet links
		foreach ($data->styles as $style => $attr)
		{
			$inline_head_styles[] = $this->styleToString($style, $attr) . "\n";
		}

		// Generate stylesheet declarations
		foreach ($data->style as $type => $content)
		{
			$inline_head_styles[] = '<style type="' . $type . '">' . "\n"
				. $content . "\n"
				. $inline_head[] = '</style>' . "\n";
		}

		// Generate script file links
		foreach ($data->scripts as $script => $attr)
		{
			$inline_head_scripts[] = $this->scriptToString($script, $attr) . "\n";
		}

		// Generate script declarations
		foreach ($data->script as $type => $content)
		{
			$inline_head_scripts[] = '<script type="' . str_replace('javascript2', 'javascript', $type) . '">' . "\n"
				. $content . "\n"
				. '</script>' . "\n";
		}

		$inline_head_scripts[] = is_array($data->custom)
			? implode("\n", $data->custom)
			: (string) $data->custom;

		if (!empty($inline_head_styles))
		{
			$data->html = '<!-- CA HEAD START STYLES -->' . implode('', $inline_head_styles) . '<!-- CA HEAD END STYLES -->' . $data->html;
		}

		if (!empty($inline_head_scripts))
		{
			$data->html = '<!-- CA HEAD START SCRIPTS -->' . implode('', $inline_head_scripts) . '<!-- CA HEAD END SCRIPTS -->' . $data->html;
		}
	}

	function styleToString($style, $attr)
	{
		$string = '<link rel="stylesheet" href="' . $style . '" type="' . $attr->mime . '"';

		$string .= !is_null($attr->media) ? ' media="' . $attr->media . '"' : '';
		$string = trim($string . ' ' . JArrayHelper::toString($attr->attribs));

		$string .= '>';

		return $string;
	}

	function scriptToString($script, $attr)
	{
		$string = '<script src="' . $script . '"';

		$string .= !is_null($attr->mime) ? ' type="' . $attr->mime . '"' : '';
		$string .= $attr->defer ? ' defer="defer"' : '';
		$string .= $attr->async ? ' async="async"' : '';

		$string .= '></script>';

		return $string;
	}

	function removeDuplicatesFromObject(&$obj, $doc, $match_value = 0)
	{
		foreach ($obj as $key => $val)
		{
			if (isset($doc[$key]) && (!$match_value || $doc[$key] == $val))
			{
				unset($obj->{$key});
			}
		}
	}

	function removeDuplicatesFromHead(&$head, $regex = '')
	{
		preg_match_all($regex, $head, $matches);

		if (empty($matches))
		{
			return;
		}

		$tags = array();

		foreach ($matches['0'] as $tag)
		{
			if (!in_array($tag, $tags))
			{
				$tags[] = $tag;
				continue;
			}

			$tag  = preg_quote($tag, '#');
			$head = preg_replace('#(' . $tag . '.*?)\s*' . $tag . '#s', '\1', $head);
		}
	}

	function protect(&$string)
	{
		RLProtect::protectFields($string);
		RLProtect::protectSourcerer($string);
	}

	function protectTags(&$string)
	{
		RLProtect::protectTags($string, $this->params->protected_tags);
	}

	function unprotectTags(&$string)
	{
		RLProtect::unprotectTags($string, $this->params->protected_tags);
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	function cleanLeftoverJunk(&$string)
	{
		$this->unprotectTags($string);

		$string = preg_replace('#<\!-- (START|END): COMA_[^>]* -->#', '', $string);
		if (!$this->params->place_comments)
		{
			$string = str_replace(
				array(
					$this->params->comment_start, $this->params->comment_end,
					htmlentities($this->params->comment_start), htmlentities($this->params->comment_end),
					urlencode($this->params->comment_start), urlencode($this->params->comment_end),
				), '', $string
			);
			$string = preg_replace('#' . preg_quote($this->params->message_start, '#') . '.*?' . preg_quote($this->params->message_end, '#') . '#', '', $string);
		}
	}

	function getByURL($url, $cache)
	{
		if ($cache)
		{
			$cacheid = $url . '_' . JFactory::getLanguage()->getTag() . '_' . implode('.', $this->aid);

			$this->cache->setCaching(1);
			$html = $this->cache->get($cacheid);
			if ($html)
			{
				$this->cache->store($html, $cacheid);

				return $html;
			}
		}

		$data = $this->getDataByURL($url);

		if ($cache)
		{
			$this->cache->store($data, $cacheid);
		}

		return $data;
	}

	function getDataByURL($url)
	{
		$url .= (strpos($url, '?') === false ? '?' : '&')
			. 'tmpl=component&rendercomponent=1&lang=' . JFactory::getLanguage()->getTag();
		$cookies = $this->getCookies();

		if ($this->params->force_curl)
		{
			return $this->getDataByCurl($this->getUrl($url), $cookies);
		}

		$data = $this->getDataByGetHttp($this->getUrl($url, $cookies));

		if (!empty($data))
		{
			return $data;
		}

		// Fall back on Curl if html is empty or not a json string
		return $this->getDataByCurl($this->getUrl($url), $cookies);
	}

	function convertHtmlToObject($data)
	{
		if (empty($data) || $data == '{}')
		{
			return false;
		}

		// remove possible leading encoding characters
		$data = preg_replace('#^.*?\{#', '{', $data);

		$data = json_decode($data);
		if (is_null($data) || empty($data))
		{
			return false;
		}

		return $data;
	}

	function getDataByGetHttp($url)
	{
		// Site uses Kerberos authentication: use curl instead
		// Site is behind a login: use curl instead
		if (
			$this->params->use_negotiate_authentication
			|| $this->getAuthenticationCredentials()
		)
		{
			return false;
		}

		try
		{
			$html = JHttpFactory::getHttp()->get($url, null, $this->params->timeout)->body;
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		return $this->convertHtmlToObject($html);
	}

	public function getCookies()
	{
		$cookies = array();

		foreach ($_COOKIE as $k => $v)
		{
			// Only include hexadecimal keys
			if (!preg_match('#^[a-f0-9]+$#si', $k))
			{
				continue;
			}

			$cookies[] = $k . '=' . $v;
		}

		return $cookies;
	}

	public function getUrl($url, $cookies = '')
	{
		if (!empty($cookies))
		{
			$url .= '&' . implode('&', $cookies);
		}

		// Pass url through the JRoute if it is a non-SEF url
		if (strpos($url, 'index.php?') !== false)
		{
			$url = JRoute::_($url);
		}

		return JUri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port')) . '/' . ltrim($url, '/');
	}

	public function getTagCharacters($quote = false)
	{
		if (!isset($this->params->tag_character_start))
		{
			list($this->params->tag_character_start, $this->params->tag_character_end) = explode('.', $this->params->tag_characters);
		}

		$start = $this->params->tag_character_start;
		$end   = $this->params->tag_character_end;

		if ($quote)
		{
			$start = preg_quote($start, '#');
			$end   = preg_quote($end, '#');
		}

		return array($start, $end);
	}

	private function getDivTags($data)
	{
		list($tag_start, $tag_end) = $this->getTagCharacters(true);

		return RLTags::getDivTags($data['start_div'], $data['end_div'], $tag_start, $tag_end);
	}

	/* CURL methods */
	function getDataByCurl($url, $cookies = '')
	{
		if (!function_exists('curl_init'))
		{
			return false;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->params->timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->params->timeout);

		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if (!empty($cookies))
		{
			curl_setopt($ch, CURLOPT_COOKIESESSION, false); // False to keep all cookies of previous session
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $cookies));
		}

		if (!empty($_POST))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
		}

		$this->setCurlAuthentication($ch);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3); // stop after 3 redirects
		$html = curl_exec($ch);

		curl_close($ch);

		return $this->convertHtmlToObject($html);
	}

	private function setCurlAuthentication(&$ch)
	{
		if ($this->params->use_negotiate_authentication)
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_GSSNEGOTIATE);
			curl_setopt($ch, CURLOPT_USERPWD, $this->params->negotiate_login . ':' . $this->params->negotiate_password);

			return;
		}

		if (!$credentials = $this->getAuthenticationCredentials())
		{
			return;
		}

		list($username, $password) = $credentials;

		// Send authentication
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password); // set referer on redirect
	}

	private function getAuthenticationCredentials()
	{
		if (!is_null($this->credentials))
		{
			return $this->credentials;
		}

		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$this->credentials = array($_SERVER['PHP_AUTH_USER'], isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '');

			return $this->credentials;
		}

		if (isset($_SERVER['HTTP_AUTHENTICATION'])
			&& strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'basic') === 0
		)
		{
			$this->credentials = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

			return $this->credentials;
		}

		return false;
	}
}
