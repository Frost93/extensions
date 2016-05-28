<?php
/**
 * @package         Content Templater
 * @version         6.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

/**
 * System Plugin that places a Content Templater code block into the text
 */
class PlgSystemContentTemplaterHelper
{
	var $params = null;

	public function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * place content on page load
	 */
	public function onAfterDispatch()
	{
		if (!$buffer = RLFunctions::getComponentBuffer())
		{
			return;
		}

		$editors = $this->getEditors($buffer);

		if (empty($editors))
		{
			return;
		}

		require_once __DIR__ . '/helpers/items.php';
		$helper = new PlgSystemContentTemplaterHelperItems($this->params);
		$items  = $helper->getItems();

		if (empty($items))
		{
			return;
		}

		$this->placeContent($buffer, $editors);


		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function placeContent(&$buffer, $editors)
	{
		require_once __DIR__ . '/helpers/content.php';
		$class   = new PlgSystemContentTemplaterHelperContent($this->params, $editors);
		$content = $class->get();

		$buffer .= '<div style="display:none;" class="contenttemplater_data">'
			. $content
			. '</div>';
	}

	function getEditors($buffer)
	{
		if (strpos($buffer, '<textarea') === false)
		{
			return false;
		}

		// Editor is TinyMCE and using javascript to place buttons (J3.5+)
		if (JVERSION >= '3.5' && strpos($buffer, 'tinyMCE') !== false)
		{
			$buffer = $this->getDocumentScript();
		}

		if (!preg_match_all('#rl_ct_button-([a-z0-9-_]+)#s', $buffer, $matches))
		{
			return false;
		}

		return array_unique($matches['1']);
	}

	function getDocumentScript()
	{
		$script = JFactory::getDocument()->_script;

		return isset($script['text/javascript']) ? $script['text/javascript'] : '';
	}
}
