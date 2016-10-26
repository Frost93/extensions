<?php
/**
 * @package         Email Protector
 * @version         3.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/text.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/protect.php';

RLFunctions::loadLanguage('plg_system_emailprotector');

/**
 * Plugin that places components
 */
class PlgSystemEmailProtectorHelper
{
	private $name   = 'Email Protector';
	private $params = null;

	public function __construct(&$params)
	{
		$this->params = $params;

		$this->params->atrr_pre  = 'data-ep-a' . substr(md5('a' . rand(1000, 9999)), 0, 4);
		$this->params->atrr_post = 'data-ep-b' . substr(md5('b' . rand(1000, 9999)), 0, 4);

		// email@domain.com
		$this->params->regex_email = '([\w\.\-]+\@(?:[a-z0-9\.\-]+\.)+(?:[a-z0-9\-]{2,10}))';

		$this->params->regex      = '#' . $this->params->regex_email . '#i';
		$this->params->regex_js   = '#<script[^>]*[^/]>.*?</script>#si';
		$this->params->regex_injs = '#([\'"])' . $this->params->regex_email . '\1#i';
		$this->params->regex_link = '#<a\s+((?:[^>]*\s+)?)href\s*=\s*"mailto:(' . $this->params->regex_email . '(?:\?[^"]+)?)"((?:\s+[^>]*)?)>(.*?)</a>#si';
	}

	public function onContentPrepare(&$article, $context, $params)
	{
		if (
		(JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && !RLFunctions::isFeed()))
		)
		{
			return;
		}

		$area    = isset($article->created_by) ? 'articles' : 'other';
		$context = (($params instanceof JRegistry) && $params->get('rl_search')) ? 'com_search.' . $params->get('readmore_limit') : $context;

		RLHelper::processArticle($article, $context, $this, 'protectEmails', array($area, $context));
	}

	public function onAfterDispatch()
	{
		// only in html or feed
		if (JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && !RLFunctions::isFeed())
		)
		{
			return;
		}

		if (JFactory::getDocument()->getType() == 'html')
		{
			$style = '/* START: ' . $this->name . ' styles */ '
				. $this->getGloabalCss()
				. ' /* END: ' . $this->name . ' styles */';

			$script = '/* START: ' . $this->name . ' scripts */ '
				. $this->getGloabalJs()
				. ' /* END: ' . $this->name . ' scripts */';

			if (JFactory::getApplication()->input->get('tmpl', 'index') == 'index')
			{
				JFactory::getDocument()->addStyleDeclaration($style);
				JFactory::getDocument()->addScriptDeclaration($script);
			}
		}

		if (!$buffer = RLFunctions::getComponentBuffer())
		{
			return;
		}

		$this->protectEmails($buffer, 'component');

		if (JFactory::getDocument()->getType() == 'html'
			&& JFactory::getApplication()->input->get('tmpl', 'index') != 'index'
		)
		{
			$buffer = '<style type="text/css">' . $style . '</style>'
				. '<script type="text/javascript">' . $script . '</script>'
				. $buffer;
		}

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && !RLFunctions::isFeed())
		)
		{
			return;
		}

		$html = JFactory::getApplication()->getBody();
		if ($html == '')
		{
			return;
		}

		// only do stuff in body
		list($pre, $body, $post) = RLText::getBody($html);
		$this->protectEmails($body);
		$html = $pre . $body . $post;

		if (JFactory::getDocument()->getType() != 'html')
		{
			JFactory::getApplication()->setBody($html);

			return;
		}

		if (strpos($html, 'addCloakedMailto(') === false)
		{
			// remove style and script if no emails are found
			$html = preg_replace('#((?:;\s*)?)(;?)/\* START: Email Protector .*?/\* END: Email Protector [a-z]* \*/\s*#s', '\1', $html);

			$this->cleanLeftoverJunk($html);

			JFactory::getApplication()->setBody($html);

			return;
		}

		// correct attribut ids in possible cached modules/content
		$html = preg_replace('# data-ep-a[a-z0-9]{4}=#s', ' ' . $this->params->atrr_pre . '=', $html);
		$html = preg_replace('# data-ep-b[a-z0-9]{4}=#s', ' ' . $this->params->atrr_post . '=', $html);

		RLProtect::removeInlineComments($html, $this->name);

		$this->cleanLeftoverJunk($html);

		JFactory::getApplication()->setBody($html);
	}

	function getGloabalCss()
	{
		$style = "
			.cloaked_email span:before {
				content: attr(" . $this->params->atrr_pre . ");
			}
			.cloaked_email span:after {
				content: attr(" . $this->params->atrr_post . ");
			}
		";

		return $this->compressCssJs($style);
	}

	function getGloabalJs()
	{
		/* Below javascript is minified via http://closure-compiler.appspot.com/home
			var emailProtector = ( emailProtector || {} );
			emailProtector.addCloakedMailto = function(clss, link) {
				var els = document.querySelectorAll("." + clss);

				for (i = 0; i < els.length; i++) {
					var el = els[i];
					var spans = el.getElementsByTagName("span");
					var pre = "";
					var post = "";
					el.className = el.className.replace(" " + clss, "");

					for (var ii = 0; ii < spans.length; ii++) {
						var attribs = spans[ii].attributes;
						for (var iii = 0; iii < attribs.length; iii++) {
							if(attribs[iii].nodeName.toLowerCase().indexOf("data-ep-a") === 0) {
								pre += attribs[iii].value;
							}
							if(attribs[iii].nodeName.toLowerCase().indexOf("data-ep-b") === 0) {
								post = attribs[iii].value + post;
							}
						}
					}

					if (!post) {
						return;
					}

					el.innerHTML = pre + post;

					if (!link) {
						return;
					}

					el.parentNode.href = "mailto:" + pre + post;
				}
			}
		*/
		$script = 'var emailProtector=emailProtector||{};emailProtector.addCloakedMailto=function(g,l){var h=document.querySelectorAll("."+g);for(i=0;i<h.length;i++){var b=h[i],k=b.getElementsByTagName("span"),e="",c="";b.className=b.className.replace(" "+g,"");for(var f=0;f<k.length;f++)for(var d=k[f].attributes,a=0;a<d.length;a++)0===d[a].nodeName.toLowerCase().indexOf("data-ep-a")&&(e+=d[a].value),0===d[a].nodeName.toLowerCase().indexOf("data-ep-b")&&(c=d[a].value+c);if(!c)break;b.innerHTML=e+c;if(!l)break;b.parentNode.href="mailto:"+e+c}};';

		return $this->compressCssJs($script);
	}

	function compressCssJs($string)
	{
		return preg_replace('#\s\s*#s', ' ', preg_replace('#\n\s*#s', ' ', trim($string)));
	}

	function protectEmails(&$string, $area = 'article', $context = '')
	{
		if (!is_string($string) || $string == '')
		{
			return;
		}

		// Check if tags are in the text snippet used for the search component
		if (strpos($context, 'com_search.') === 0)
		{
			$limit = explode('.', $context, 2);
			$limit = (int) array_pop($limit);

			$string_check = substr($string, 0, $limit);

			if (strpos($string_check, '@') === false)
			{
				return;
			}
		}

		// No action needed if no @ is found
		if (strpos($string, '@') === false)
		{
			return;
		}

		$this->protectEmailsInJavascript($string);

		$this->protect($string);

		$this->protectEmailsInString($string);

		RLProtect::unprotect($string);
	}

	protected function protectEmailsInJavascript(&$string)
	{
		if (
			!$this->params->protect_in_js
			|| strpos($string, '</script>') === false
			|| !preg_match_all($this->params->regex_js, $string, $matches, PREG_SET_ORDER)
		)
		{
			return;
		}

		foreach ($matches as $match)
		{
			$script = $match[0];
			$this->protectEmailsInJavascriptTag($script);

			$string = str_replace($match[0], $script, $string);
		}
	}

	protected function protectEmailsInJavascriptTag(&$string)
	{
		while (preg_match($this->params->regex_injs, $string, $regs, PREG_OFFSET_CAPTURE))
		{
			$protected = str_replace(
				array('.', '@'),
				array(
					$regs[1][0] . ' + ' . 'String.fromCharCode(46)' . ' + ' . $regs[1][0],
					$regs[1][0] . ' + ' . 'String.fromCharCode(64)' . ' + ' . $regs[1][0],
				),
				$regs[0][0]
			);
			$string    = substr_replace($string, $protected, $regs[0][1], strlen($regs[0][0]));
		}
	}

	protected function protectEmailsInString(&$string)
	{
		// Do not protect if {emailprotector=off} or {emailcloak=off} is found in text
		if (
			strpos($string, '{emailprotector=off}') !== false
			|| strpos($string, '{emailcloak=off}') !== false
			|| strpos($string, '<!-- EPOFF -->') !== false
		)
		{
			$string = str_replace(
				array(
					'<p>{emailprotector=off}</p>', '{emailprotector=off}',
					'<p>{emailcloak=off}</p>', '{emailcloak=off}',
				),
				'<!-- EPOFF -->',
				$string
			);

			return;
		}

		if (strpos($string, '@') === false)
		{
			return;
		}

		list($pre_string, $string, $post_string) = RLText::getContentContainingSearches(
			$string,
			array('@')
		);

		// Fix derivatives of link code <a href="http://mce_host/ourdirectory/email@domain.com">email@domain.com</a>
		// This happens when inserting an email in TinyMCE, cancelling its suggestion to add the mailto: prefix...
		if (strpos($string, 'mce_host') !== false)
		{
			$string = preg_replace('#"http://mce_host([\x20-\x7f][^<>]+/)#i', '"mailto:', $string);
		}

		// Search for derivatives of link code <a href="mailto:email@domain.com">anytext</a>
		preg_match_all($this->params->regex_link, $string, $emails, PREG_SET_ORDER);

		if (!empty($emails))
		{
			foreach ($emails as $email)
			{
				$mail      = str_replace('&amp;', '&', $email['2']);
				$protected = $this->protectEmail($mail, $email['5'], $email['1'], $email['4']);
				$string    = substr_replace($string, $protected, strpos($string, $email['0']), strlen($email['0']));
			}
		}

		if (strpos($string, '@') === false)
		{
			$string = $pre_string . $string . $post_string;

			return;
		}

		RLProtect::protectHtmlTags($string);

		if (strpos($string, '@') === false)
		{
			$string = $pre_string . $string . $post_string;

			return;
		}

		// Search for plain text email@domain.com
		preg_match_all($this->params->regex, $string, $emails, PREG_SET_ORDER);

		if (!empty($emails))
		{
			foreach ($emails as $email)
			{
				$protected = $this->protectEmail('', $email['1']);
				$string    = substr_replace($string, $protected, strpos($string, $email['0']), strlen($email['0']));
			}
		}

		$string = $pre_string . $string . $post_string;
	}

	/**
	 * Protects the email address with a series of spans
	 *
	 * @param   string  $mailto The mailto address in the surrounding link.
	 * @param   string  $text   Text containing possible emails
	 * @param   boolean $pre    Prepending attributes in <a> tag
	 * @param   boolean $post   Ending attributes in <a> tag
	 *
	 * @return  string  The cloaked email.
	 */
	protected function protectEmail($mailto, $text = '', $pre = '', $post = '')
	{
		$id = 0;

		// In FEEDS

		if (RLFunctions::isFeed())
		{
			// Replace with custom text
			if ($this->params->protect_in_feeds == 2)
			{
				return JText::_($this->params->feed_text);
			}

			// Replace with spoofed email
			if (!$text)
			{
				$text = $mailto;
			}

			self::spoofEmails($text, 0);

			return $text;
		}

		// In HTML

		if ($text)
		{
			if ($this->params->spoof)
			{
				self::spoofEmails($text);
			}
			while (preg_match($this->params->regex, $text, $regs, PREG_OFFSET_CAPTURE))
			{
				$id        = 'ep_' . substr(md5(rand()), 0, 8);
				$protected = self::createSpans($regs[1][0], $id);
				$text      = substr_replace($text, $protected, $regs[1][1], strlen($regs[1][0]));
			}
			if ($id && !$mailto && $this->params->mode == 1)
			{
				return self::createLink($text, $id, $pre, $post);
			}
		}

		if ($this->params->mode && $mailto)
		{
			$id = 'ep_' . substr(md5(rand()), 0, 8);
			if ($text)
			{
				$text .= self::createSpans($mailto, $id, 1);
			}
			else
			{
				$text = self::createSpans($mailto, $id, 1);
				if ($this->params->spoof)
				{
					$id = 'ep_' . substr(md5(rand()), 0, 8);
					self::spoofEmails($mailto);
					$text .= self::createSpans($mailto, $id, 0);
				}
			}

			return self::createLink($text, $id, $pre, $post);
		}

		if ($id)
		{
			return self::createOutput($text, $id);
		}

		return $text;
	}

	/**
	 * Replace @ and dots with [AT] and [DOT]
	 *
	 * @param   string $text Text containing possible emails
	 */
	protected function spoofEmails(&$text, $ishtml = 1)
	{
		while (preg_match($this->params->regex, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			if ($ishtml)
			{
				$replace = array('<small> ' . JText::_('EP_AT') . ' </small>', '<small> ' . JText::_('EP_DOT') . ' </small>');
			}
			else
			{
				$replace = array(' ' . JText::_('EP_AT') . ' ', ' ' . JText::_('EP_DOT') . ' ');
			}

			$email = str_replace(array('@', '.'), $replace, $regs[1][0]);
			$text  = substr_replace($text, $email, $regs[1][1], strlen($regs[1][0]));
		}
	}

	/**
	 * Convert text to encoded spans.
	 *
	 * @param   string  $string Text to convert.
	 * @param   string  $id     ID of the main span.
	 * @param   boolean $hide   Hide the spans?
	 *
	 * @return  string   The encoded string.
	 */
	protected function createSpans($string, $id = 0, $hide = 0)
	{
		$split = str_split($string);
		$size  = ceil(count($split) / 6);
		$parts = array('', '', '', '', '', '');
		foreach ($split as $i => $c)
		{
			$v   = ($c == '@' || (strlen($c) === 1 && rand(0, 2))) ? '&#' . ord($c) . ';' : $c;
			$pos = floor($i / $size);
			$parts[$pos] .= $v;
		}

		$parts = array(
			array($parts['0'], $parts['5']),
			array($parts['1'], $parts['4']),
			array($parts['2'], $parts['3']),
		);

		$html = array();

		$html[] = '<span class="cloaked_email' . ($id ? ' ' . $id : '') . '"' . ($hide ? ' style="display:none;"' : '') . '>';
		foreach ($parts as $part)
		{
			$atrr = array(
				$this->params->atrr_pre . '="' . $part['0'] . '"',
				$this->params->atrr_post . '="' . $part['1'] . '"',
			);
			shuffle($atrr);
			$html[] = '<span ' . implode(' ', $atrr) . '>';
		}
		$html[] = '</span></span></span></span>';

		return implode('', $html);
	}

	/**
	 * Create output with comment tag and script
	 *
	 * @param   string $text Inner text.
	 * @param   string $id   ID of the main span.
	 *
	 * @return  string   The html.
	 */
	protected function createOutput($text, $id)
	{
		return '<!-- ' . JText::_('EP_MESSAGE_PROTECTED') . ' -->' . $text
		. '<script type="text/javascript">emailProtector.addCloakedMailto("' . $id . '", 0);</script>';
	}

	/**
	 * Create output with comment tag and script and a link around the text
	 *
	 * @param   string  $text Inner text.
	 * @param   string  $id   ID of the main span.
	 * @param   boolean $pre  Prepending attributes in <a> tag
	 * @param   boolean $post Ending attributes in <a> tag
	 *
	 * @return  string   The html.
	 */
	protected function createLink($text, $id, $pre = '', $post = '')
	{
		return
			'<a ' . $pre . 'href="javascript:/* ' . htmlentities(JText::_('EP_MESSAGE_PROTECTED'), ENT_COMPAT, 'UTF-8') . '*/"' . $post . '>'
			. $text
			. '</a>'
			. '<script type="text/javascript">emailProtector.addCloakedMailto("' . $id . '", 1);</script>';
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	private function cleanLeftoverJunk(&$string)
	{
		$string = str_replace('<!-- EPOFF -->', '', $string);
	}

	private function protect(&$string)
	{
		RLProtect::protectFields($string);
		RLProtect::protectScripts($string);
		RLProtect::protectSourcerer($string);
	}
}
