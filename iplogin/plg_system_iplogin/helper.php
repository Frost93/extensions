<?php
/**
 * @package         IP Login
 * @version         3.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

RLFunctions::loadLanguage('plg_system_iplogin');

/**
 * Plugin to log in automatically by IP address
 */
class PlgSystemIPLoginHelper
{
	var $ip        = null;
	var $url_query = null;
	var $url_keys  = null;

	public function __construct(&$params)
	{
		JFormHelper::addFieldPath(__DIR__ . '/fields');

		$this->params = $params;
	}

	public function logIn()
	{
		$this->user = JFactory::getUser();

		if (!$this->user->guest)
		{
			// If logged in, remove key from URL
			$this->removeKeyFromLoggedInURL();

			return;
		}

		// If not logged in, try to log in and remove key from URL
		$this->logInUser();
	}

	private function removeKeyFromLoggedInURL()
	{
		if (empty($this->user->params))
		{
			return;
		}

		if (
			!$this->params->remove_key
			|| ($this->params->remove_key == 'admin' && !JFactory::getApplication()->isAdmin())
			|| ($this->params->remove_key == 'site' && !JFactory::getApplication()->isSite())
		)
		{
			return;
		}

		// Return if there is nothing in the url that looks like a key
		$url_keys = $this->getUrlKeys();

		if (empty($url_keys))
		{
			return;
		}

		if (is_string($this->user->params))
		{
			$this->user->params = json_decode($this->user->params);
		}

		$max = 1;
		for ($i = 1; $i <= $max; $i++)
		{
			// Check if the user key for this IP is present in the URL
			$user_key = isset($this->user->params->{'ip' . $i . '_key'}) ? trim($this->user->params->{'ip' . $i . '_key'}) : '';

			if (empty($user_key)
				|| !in_array($user_key, $this->url_keys)
			)
			{
				continue;
			}

			// Remove the key from the url
			$url = $this->removeKeyFromURL($user_key);

			// Redirect
			JFactory::getApplication()->redirect($url);

			return;
		}
	}

	private function removeKeyFromURL($key)
	{
		$url       = JUri::getInstance()->current();
		$url_query = $this->getUrlQuery();

		// Remove key from query array if settings allow it
		if (
			$this->params->remove_key
			|| ($this->params->remove_key == 'admin' && JFactory::getApplication()->isAdmin())
			|| ($this->params->remove_key == 'site' && JFactory::getApplication()->isSite())
		)
		{
			$url_query = array_diff($url_query, array($key));
		}

		if (empty($url_query))
		{
			return $url;
		}

		// Add query to url
		return $url . '?' . implode('&', $url_query);
	}

	private function logInUser()
	{
		// Return if no IP address can be found (shouldn't happen, but who knows)
		if (!$this->getIp())
		{
			return;
		}

		// Return if there is nothing in the url that looks like a key
		$url_keys = $this->getUrlKeys();

		if (empty($url_keys))
		{
			return;
		}

		// Return if no user is found matching the ip and given key
		if (!$this->findUser())
		{
			return;
		}

		// Remove the key from the url
		$url = $this->removeKeyFromURL($this->user->key);
		$this->redirect($url);
	}

	private function getIp()
	{
		$this->ip = trim($_SERVER['REMOTE_ADDR']);

		if (empty($this->ip))
		{
			return false;
		}

		// Return false if IP address in the wrong format or is IPv6 format (not supported yet
		if (strpos($this->ip, '.') === false || strpos($this->ip, ':') !== false)
		{
			return false;
		}

		return true;
	}

	private function getUrlKeys()
	{
		if (!is_null($this->url_keys))
		{
			return $this->url_keys;
		}

		// Return if there is no URL query
		$url_query = $this->getUrlQuery();

		if (empty($url_query))
		{
			return false;
		}

		$this->url_keys = array();

		foreach ($url_query as $query_part)
		{
			if (strpos($query_part, '=') !== false)
			{
				continue;
			}

			$this->url_keys[] = trim($query_part);
		}

		return $this->url_query;
	}

	private function getUrlQuery()
	{
		if (!is_null($this->url_query))
		{
			return $this->url_query;
		}

		$this->url_query = explode('&', JUri::getInstance()->getQuery());

		return $this->url_query;
	}

	function redirect($url)
	{
		// Construct a response
		jimport('joomla.user.authentication');
		JAuthentication::getInstance();
		JPluginHelper::importPlugin('user');

		// Construct the options
		$options = array(
			'action'       => 'core.login.' . (JFactory::getApplication()->getName() == 'site' ? 'site' : 'admin'),
			'group'        => 'Public Backend',
			'autoregister' => '',
			'entry_url'    => $url,
		);

		// Construct the response-object
		$response = new JAuthenticationResponse;

		$response->type          = 'Joomla';
		$response->email         = $this->user->email;
		$response->fullname      = $this->user->name;
		$response->username      = $this->user->username;
		$response->password      = $this->user->password;
		$response->language      = $this->user->getParam('language');
		$response->status        = JAuthentication::STATUS_SUCCESS;
		$response->error_message = null;

		// Run the login-event
		JFactory::getApplication()->triggerEvent('onUserLogin', array((array) $response, $options));

		// Redirect
		JFactory::getApplication()->redirect($url);
	}

	private function findUser()
	{
		$user_ids = $this->getUserIds();

		if (empty($user_ids))
		{
			return false;
		}

		foreach ($user_ids as $id)
		{
			$this->user = JFactory::getUser($id);

			$this->user->params = json_decode($this->user->params);

			if ($this->userMatchesIP())
			{
				return true;
			}
		}

		return false;
	}

	private function getUserIds()
	{
		if (empty($this->url_keys))
		{
			return false;
		}

		$db = JFactory::getDbo();

		$key_matches = array();
		foreach ($this->url_keys as $key)
		{
			$key_matches[] = $db->quoteName('params') . ' LIKE ' . $db->quote('%_key":"' . $key . '"%');
		}

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('block') . ' = 0')
			->where($db->quoteName('activation') . ' = 0')
			->where('(' . implode(' OR ', $key_matches) . ')');

		$db->setQuery($query);

		return $db->loadColumn();
	}

	private function userMatchesIP()
	{
		$total = 1;
		for ($i = 1; $i <= $total; $i++)
		{
			$user_enabled = isset($this->user->params->{'ip' . $i . '_enabled'}) ? trim($this->user->params->{'ip' . $i . '_enabled'}) : '';

			if (!$user_enabled
				|| ($user_enabled == 'admin' && !JFactory::getApplication()->isAdmin())
				|| ($user_enabled == 'site' && JFactory::getApplication()->isAdmin())
			)
			{
				continue;
			}

			$user_key = isset($this->user->params->{'ip' . $i . '_key'}) ? trim($this->user->params->{'ip' . $i . '_key'}) : '';

			if (empty($user_key)
				|| !in_array($user_key, $this->url_keys)
			)
			{
				continue;
			}

			$user_ip = isset($this->user->params->{'ip' . $i . '_ip'}) ? trim($this->user->params->{'ip' . $i . '_ip'}) : '';

			if (empty($user_ip)
				|| !$this->matchesIP($user_ip)
			)
			{
				continue;
			}

			$this->user->key = $user_key;

			return true;
		}

		return false;
	}

	private function matchesIP($user_ip)
	{

		return $user_ip == $this->ip;
	}


	public function addFieldsToUserForm($form)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		$name = $form->getName();
		if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		// load the admin language file
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('plg_system_iplogin');

		// Add the registration fields to the form.
		JForm::addFormPath(__DIR__ . '/form');
		$form->loadFile('ips', false);

		return true;
	}
}
