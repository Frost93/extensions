<?php
/**
 * @package         Cache Cleaner
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('plg_system_cachecleaner');

class PlgSystemCacheCleanerHelper
{
	var $helpers      = array();
	var $type         = '';
	var $show_message = false;

	public function __construct(&$params)
	{
		$params->size    = 0;
		$params->message = '';
		$params->error   = false;

		$this->params = $params;

		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = PlgSystemCacheCleanerHelpers::getInstance($params);

		$this->type = $this->getCleanType();
	}

	function clean()
	{

		if (!$this->type)
		{
			return;
		}

		// Load language for messaging
		RLFunctions::loadLanguage('mod_cachecleaner');

		$this->purgeCache();

		// only handle messages in html
		if (JFactory::getDocument()->getType() != 'html')
		{
			return false;
		}

		$error = $this->helpers->getParams()->error;
		if ($error)
		{
			$message = JText::_('CC_NOT_ALL_CACHE_COULD_BE_REMOVED');
			$message .= $this->helpers->getParams()->error !== true ? '<br>' . $this->helpers->getParams()->error : '';
		}
		else
		{
			$message = $this->helpers->getParams()->message ?: JText::_('CC_CACHE_CLEANED');

			if ($this->params->show_size && $this->helpers->getParams()->size)
			{
				$message .= ' (' . $this->helpers->get('cache')->getSize() . ')';
			}
		}

		if (JFactory::getApplication()->input->getInt('break'))
		{
			echo (!$error ? '+' : '') . str_replace('<br>', ' - ', $message);
			die;
		}

		if ($this->show_message && $message)
		{
			JFactory::getApplication()->enqueueMessage($message, ($error ? 'error' : 'message'));
		}
	}

	function getCleanType()
	{
		$cleancache = trim(JFactory::getApplication()->input->getString('cleancache'));

		// Clean via url
		if (!empty($cleancache))
		{
			// Return if on frontend and no secret url key is given
			if (JFactory::getApplication()->isSite() && $cleancache != $this->params->frontend_secret)
			{
				return '';
			}

			// Return if on login page
			if (JFactory::getApplication()->isAdmin() && JFactory::getUser()->get('guest'))
			{
				return '';
			}

			if (JFactory::getApplication()->input->getWord('src') == 'button')
			{
				return 'button';
			}

			$this->show_message = true;

			return 'clean';
		}

		// Clean via save task
		if ($this->passTask())
		{
			return 'save';
		}


		return '';
	}

	function passTask()
	{
		if (!$task = JFactory::getApplication()->input->get('task'))
		{
			return false;
		}

		$task = explode('.', $task, 2);
		$task = isset($task['1']) ? $task['1'] : $task['0'];
		if (strpos($task, 'save') === 0)
		{
			$task = 'save';
		}

		$tasks = array_diff(array_map('trim', explode(',', $this->params->auto_save_tasks)), array(''));

		if (empty($tasks) || !in_array($task, $tasks))
		{
			return false;
		}

		if (JFactory::getApplication()->isAdmin() && $this->params->auto_save_admin)
		{
			$this->show_message = $this->params->auto_save_admin_msg;

			return true;
		}

		if (JFactory::getApplication()->isSite() && $this->params->auto_save_front)
		{
			$this->show_message = $this->params->auto_save_front_msg;

			return true;
		}

		return false;
	}

	function purgeCache()
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		// Joomla cache
		if ($this->passType('purge'))
		{
			$this->helpers->get('joomla')->purge();
		}


		// Folders
		if ($this->passType('clean_tmp'))
		{
			$this->helpers->get('folders')->purge_tmp();
		}

		// Purge OPcache
		if ($this->passType('purge_opcache'))
		{
			$this->helpers->get('joomla')->purgeOPcache();
		}

		// Purge expired cache
		if ($this->passType('purge'))
		{
			$this->helpers->get('joomla')->purgeExpired();
		}

		// Purge update cache
		if ($this->passType('purge_updates'))
		{
			$this->helpers->get('joomla')->purgeUpdates();
		}

	}

	function passType($type)
	{
		if (empty($this->params->{$type}))
		{
			return false;
		}

		if ($this->params->{$type} == 2 && $this->type != 'button')
		{
			return false;
		}

		return true;
	}

}
