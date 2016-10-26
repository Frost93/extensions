<?php
/**
 * @package         Better Preview
 * @version         5.2.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

if (!class_exists('ContentHelperRoute'))
{
	require_once JPATH_SITE . '/components/com_content/helpers/route.php';
}

class HelperBetterPreviewHelperContentArticle extends PlgSystemBetterPreviewHelper
{
	function getArticle()
	{
		if (JFactory::getApplication()->input->get('layout', 'edit') != 'edit'
			|| !JFactory::getApplication()->input->get('id')
		)
		{
			return;
		}

		$item = $this->getItem(
			JFactory::getApplication()->input->get('id'),
			'content',
			array('name' => 'title', 'published' => 'state', 'language' => 'language', 'parent' => 'catid'),
			array('type' => 'RL_ARTICLE')
		);

		$item->url = ContentHelperRoute::getArticleRoute($item->id, $item->parent, $item->language);

		return $item;
	}

	function getArticleParents($item)
	{
		if (empty($item)
			|| JFactory::getApplication()->input->get('layout', 'edit') != 'edit'
			|| !JFactory::getApplication()->input->get('id')
		)
		{
			return false;
		}

		$parents = $this->getParents(
			$item,
			'categories',
			array('name' => 'title', 'parent' => 'parent_id', 'language' => 'language'),
			array('type' => 'JCATEGORY'),
			1
		);

		foreach ($parents as &$parent)
		{
			$parent->url = ContentHelperRoute::getCategoryRoute($parent->id, $item->language);
		}

		return $parents;
	}
}
