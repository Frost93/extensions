<?php
/**
 * @package         Advanced Template Manager
 * @version         2.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

$app  = JFactory::getApplication();
$user = JFactory::getUser();

// ACL for hardening the access to the template manager.
if (!$user->authorise('core.manage', 'com_templates'))
{
	$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

	return false;
}

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('com_templates', JPATH_ADMINISTRATOR);
RLFunctions::loadLanguage('com_advancedtemplates');

jimport('joomla.filesystem.file');

// return if Regular Labs Library plugin is not installed
if (!JFile::exists(JPATH_PLUGINS . '/system/regularlabs/regularlabs.php'))
{
	$msg = JText::_('ATP_REGULAR_LABS_LIBRARY_NOT_INSTALLED')
		. ' ' . JText::sprintf('ATP_EXTENSION_CAN_NOT_FUNCTION', JText::_('COM_ADVANCEDTEMPLATES'));
	JFactory::getApplication()->enqueueMessage($msg, 'error');

	return;
}

// give notice if Regular Labs Library plugin is not enabled
$regularlabs = JPluginHelper::getPlugin('system', 'regularlabs');
if (!isset($regularlabs->name))
{
	$msg = JText::_('ATP_REGULAR_LABS_LIBRARY_NOT_ENABLED')
		. ' ' . JText::sprintf('ATP_EXTENSION_CAN_NOT_FUNCTION', JText::_('COM_ADVANCEDTEMPLATES'));
	JFactory::getApplication()->enqueueMessage($msg, 'notice');
}

// load the Regular Labs Library language file
RLFunctions::loadLanguage('plg_system_regularlabs');

JLoader::register('AdvancedTemplatesHelper', __DIR__ . '/helpers/templates.php');

$controller = JControllerLegacy::getInstance('AdvancedTemplates');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
