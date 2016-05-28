<?php
/**
 * @package         CDN for Joomla!
 * @version         5.2.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemCDNforJoomlaInstallerScript extends PlgSystemCDNforJoomlaInstallerScriptHelper
{
	public $name           = 'CDN_FOR_JOOMLA';
	public $alias          = 'cdnforjoomla';
	public $extension_type = 'plugin';

	public function onAfterInstall()
	{
		$this->fixOldParams();
	}

	/* Fixes old params from before v4.1.0 */
	private function fixOldParams()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('extension_id'))
			->select($this->db->quoteName('params'))
			->from('#__extensions')
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('cdnforjoomla'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);

		$plugin = $this->db->loadObject();

		if (empty($plugin) || empty($plugin->params))
		{
			return;
		}

		$params = json_decode($plugin->params);

		if (empty($params))
		{
			return;
		}

		// The new web protocol setting is found, so no need to do anything
		if (isset($params->web_protocol))
		{
			return;
		}

		for ($i = 1; $i <= 5; $i++)
		{
			$setid = ($i <= 1) ? '' : '_' . (int) $i;

			$this->fixFieldTypes($params, $setid);
			$this->fixProtocol($params, $setid);
		}

		$params = json_encode($params);

		// Nothing has changed
		if ($params == $plugin->params)
		{
			return;
		}

		$query->clear()
			->update('#__extensions')
			->set($this->db->quoteName('params') . ' = ' . $this->db->quote($params))
			->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($plugin->extension_id));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	private function fixFieldTypes(&$params, $setid)
	{
		if (!isset($params->{'filetypes' . $setid}))
		{
			return;
		}

		$filetypes = $params->{'filetypes' . $setid};

		if (is_array($filetypes))
		{
			$filetypes = array_diff($filetypes, array('x'));
			$filetypes = implode(',', $filetypes);
		}

		if ($filetypes == '*')
		{
			$filetypes = 'css,js,bmp,gif,jpg,jpeg,ico,png,tif,tiff,svg,doc,docx,odt,pdf,rtf,txt';
		}

		$filetypes = str_replace('-', ',', $filetypes);

		if (isset($params->{'extratypes' . $setid}))
		{
			$filetypes .= ',' . $params->{'extratypes' . $setid};
			unset($params->{'extratypes' . $setid});
		}

		$params->{'filetypes' . $setid} = $filetypes;
	}

	private function fixProtocol(&$params, $setid)
	{
		if (!isset($params->{'enable_https' . $setid}))
		{
			return;
		}

		switch ($params->{'enable_https' . $setid})
		{
			case 2:
				$protocol = 'https';
				break;

			case 0:
				$protocol = 'http';
				break;

			case 1:
			default:
				$protocol = 'both';
				break;
		}

		$params->{'web_protocol' . $setid} = $protocol;
		unset($params->{'enable_https' . $setid});
	}
}
