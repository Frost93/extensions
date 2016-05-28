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

$user = JFactory::getUser();
if (
	$user->get('guest')
	|| (
		!$user->authorise('core.create', 'com_content')
		&& !$user->authorise('core.edit', 'com_content')
		&& !count($user->getAuthorisedCategories('com_content', 'core.create'))
		&& !count($user->getAuthorisedCategories('com_content', 'core.edit'))
	)
)
{
	JError::raiseError(403, JText::_("ALERTNOTAUTH"));
}

if (JFactory::getApplication()->isSite())
{
	$params = JComponentHelper::getParams('com_contenttemplater');
	if (!$params->get('enable_frontend', 1))
	{
		JError::raiseError(403, JText::_("ALERTNOTAUTH"));
	}
}

$class = new PlgButtonContentTemplaterData;
$class->render();
die;

class PlgButtonContentTemplaterData
{
	function render()
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		header('Content-Type: text/html; charset=utf-8');

		$id = JFactory::getApplication()->input->getInt('id');

		if (!$id)
		{
			return;
		}

		RLFunctions::stylesheet('regularlabs/popup.min.css');
		RLFunctions::stylesheet('regularlabs/style.min.css');

		$nocontent   = JFactory::getApplication()->input->getInt('nocontent', 0);
		$unprotected = (JFactory::getUser()->authorise('core.manage', 'com_contenttemplater')) ? JFactory::getApplication()->input->getInt('unprotect') : 0;

		require_once JPATH_ADMINISTRATOR . '/components/com_contenttemplater/models/item.php';

		// Create a new class of classname and set the default task: display
		$model = new ContentTemplaterModelItem;
		$item  = $model->getItem($id, false, true, true);

		$output = array();


		foreach ($item->params as $key => $val)
		{
			if ($val == ''
				|| is_object($val)
				|| isset($output[$key])
				|| strpos($key, '@') === 0
			)
			{
				continue;
			}

			if ($key == 'content' && $nocontent)
			{
				continue;
			}

			$default      = isset($item->defaults->{$key}) ? $item->defaults->{$key} : '';
			$form_default = isset($item->form_defaults->{$key}) ? $item->form_defaults->{$key} : $default;

			if ($val == $default || $val == $form_default)
			{
				continue;
			}

			if ($val == -2)
			{
				$val = '';
			}

			list($key, $val) = $this->getStr($model, $key, $val, $form_default);
			$output[$key] = $val;
		}


		$str = implode("\n", $output);

		if ($unprotected)
		{
			echo $str;
		}

		echo wordwrap(base64_encode($str), 80, "\n", 1);
	}

	function getStr(&$item, $key, $val, $default = '')
	{
		switch ($key)
		{
			case 'jform_access':
				$default = 1;
				break;
			case 'jform_categories_k2':
				$key     = 'catid';
				$default = 0;
				break;
			case 'jform_categories_zoo':
				$key     = 'categories';
				$default = '';
				break;
		}
		if (is_array($val))
		{
			$val = implode(',', $val);
		}
		if ($key != 'content')
		{
			$val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
			if (strpos($key, 'jform_') !== false)
			{

				$key = preg_replace('#jform_(params|attribs|images|urls|metadata)_#', 'jform[\1][', $key);
				$key = str_replace('jform_', 'jform[', $key) . ']';
			}
		}
		$item->replaceVars($val);

		return array($key, '[CT]' . $key . '[CT]' . $default . '[CT]' . $val . '[/CT]');
	}
}
