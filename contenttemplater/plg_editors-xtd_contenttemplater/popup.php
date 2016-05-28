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

// load the admin language file
require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
RLFunctions::loadLanguage('plg_editors-xtd_contenttemplater');

require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
$params = RLParameters::getInstance()->getComponentParams('contenttemplater');

$id     = JFactory::getApplication()->input->get('id');
$editor = JFactory::getApplication()->input->get('editor');

require_once JPATH_PLUGINS . '/system/contenttemplater/helpers/buttons.php';
$helper = new PlgSystemContentTemplaterHelperButtons($params, $editor);
$data   = $helper->get();

require_once JPATH_PLUGINS . '/system/contenttemplater/helpers/content.php';
$helper = new PlgSystemContentTemplaterHelperContent($params, array($editor));

$content = '';

foreach ($data as $item)
{
	if ($item->id . '' !== $id)
	{
		continue;
	}

	$content = $helper->getContentHtmlModal($item);
	break;
}

echo str_replace('[:CT-EDITOR:]', $editor, $content);
