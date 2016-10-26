<?php
/**
 * @package         ReReplacer
 * @version         7.1.4
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
require_once JPATH_LIBRARIES . '/regularlabs/helpers/helper.php';

RLFunctions::loadLanguage('plg_system_rereplacer');

/**
 * Plugin that replaces stuff
 */
class PlgSystemReReplacerHelper
{
	var $helpers = array();

	public function __construct(&$params)
	{
		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = PlgSystemReReplacerHelpers::getInstance($params);
	}

	public function onContentPrepare(&$article, &$context)
	{
		$items = $this->helpers->get('items')->getItemList('articles');
		$this->helpers->get('items')->filterItemList($items, $article);

		foreach ($items as $item)
		{
			if (!$item->enable_in_title)
			{
				$title = isset($article->title) ? $article->title : '';
			}

			RLHelper::processArticle($article, $context, $this, 'replace', array($item, &$article));

			if (!$item->enable_in_title && $title)
			{
				$article->title = $title;
			}
		}
	}

	public function onAfterDispatch()
	{
		// FEED
		if (
			isset(JFactory::getDocument()->items)
			&& (
				RLFunctions::isFeed()
				|| JFactory::getApplication()->input->get('option') == 'com_acymailing'
			)
		)
		{
			$context = 'feed';
			$items   = JFactory::getDocument()->items;
			foreach ($items as $item)
			{
				$this->onContentPrepare($item, $context);
			}
		}

		// only in html
		if (JFactory::getDocument()->getType() != 'html')
		{
			return;
		}

		if (!$buffer = RLFunctions::getComponentBuffer())
		{
			return;
		}

		$this->helpers->get('tag')->tagArea($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		$html = JFactory::getApplication()->getBody();

		if ($html == '')
		{
			return;
		}

		$this->helpers->get('replace')->replaceInAreas($html);

		$this->helpers->get('clean')->cleanLeftoverJunk($html);

		JFactory::getApplication()->setBody($html);
	}

	public function replace(&$string, $item, &$article)
	{
		$this->helpers->get('replace')->replace($string, $item, $article);
	}
}
