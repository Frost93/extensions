<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         6.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/*
 * This is only used to uninstall the NoNumber Extension Manager and redirect to the new Regular Labs Extension Manager
 */

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_nonumbermanager'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

if (JFactory::getApplication()->input->get('option') != 'com_nonumbermanager')
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

if (JFactory::getApplication()->input->get('view') == 'process')
{
	echo '
	<div class="alert alert-warning">
	<p class="alert-message">
	Please close this popup before retying to update...
	<script>if(confirm("The page needs to reload before you can update the other extensions."))window.parent.location.href = window.parent.location;</script>
	</p>
	</div>
	';
	return;
}

$db    = JFactory::getDbo();
$query = $db->getQuery(true)
	->select('extension_id')
	->from('#__extensions')
	->where($db->quoteName('element') . ' = ' . $db->quote('com_nonumbermanager'));

$db->setQuery($query);
$id = $db->loadResult();

$installer = new JInstaller;
$installer->uninstall('component', $id);

JFactory::getApplication()->enqueueMessage('NoNumber is now known as Regular Labs.', 'notice');

JFactory::getApplication()->redirect('index.php?option=com_regularlabsmanager');
