<?php
/**
 * @package         NoNumber Framework
 * @version         16.10.22333
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class NNFrameworkNeeded
{
	public static function getOldInstalledExtensions()
	{
		$extensions = array(
			'addtomenu'          => array(5, 'Add to Menu'),
			'adminbardocker'     => array(0, 'AdminBar Docker (discontinued)'),
			'advancedmodules'    => array(6, 'Advanced Module Manager'),
			'advancedtemplates'  => array(2, 'Advanced Template Manager'),
			'articlesanywhere'   => array(5, 'Articles Anywhere'),
			'betterpreview'      => array(5, 'Better Preview'),
			'cachecleaner'       => array(5, 'Cache Cleaner'),
			'cdnforjoomla'       => array(5, 'CDN for Joomla!'),
			'componentsanywhere' => array(3, 'Components Anywhere'),
			'contenttemplater'   => array(6, 'Content Templater'),
			'dbreplacer'         => array(5, 'DB Replacer'),
			'dummycontent'       => array(3, 'Dummy Content'),
			'emailprotector'     => array(3, 'Email Protector'),
			'geoip'              => array(1, 'GeoIP'),
			'iplogin'            => array(3, 'IP Login'),
			'modalizer'          => array(7, 'Modalizer (now Modals)'),
			'modals'             => array(7, 'Modals'),
			'modulesanywhere'    => array(5, 'Modules Anywhere'),
			'nonumbermanager'    => array(6, 'NoNumber Extension Manager (now Regular Labs Extension Manager)'),
			'rereplacer'         => array(7, 'ReReplacer'),
			'slider'             => array(6, 'Slider (now Sliders)'),
			'sliders'            => array(6, 'Sliders'),
			'snippets'           => array(5, 'Snippets'),
			'sourcerer'          => array(6, 'Sourcerer'),
			'tabber'             => array(6, 'Tabber (now Tabs)'),
			'tabs'               => array(6, 'Tabs'),
			'timedstyles'        => array(0, 'Timed Styles (discontinued)'),
			'tooltips'           => array(5, 'Tooltips'),
			'whatnothing'        => array(11, 'What? Nothing!'),
		);

		$still_installed = array();

		foreach ($extensions as $extension => $data)
		{
			if (!$current_version = self::getCurrentVersion($extension))
			{
				// Extension not found
				continue;
			}

			$version = $data['0'];

			if (!$version || $current_version < $version)
			{
				// An extension (version) is installed that still needs the NoNumber framework
				$still_installed[] = $data['1'];
			}
		}

		// No extensions found that still needs the NoNumber framework
		return $still_installed;
	}

	private static function getCurrentVersion($extension)
	{
		if (!$xml = self::getXmlFile($extension))
		{
			return;
		}

		$xml = JInstaller::parseXMLInstallFile($xml);

		if (!isset($xml['version']))
		{
			return;
		}

		return $xml['version'];
	}

	private static function getXmlFile($extension)
	{
		jimport('joomla.filesystem.file');

		$paths = array(
			JPATH_ADMINISTRATOR . '/components/com_' . $extension . '/' . $extension . '.xml',
			JPATH_ADMINISTRATOR . '/modules/mod_' . $extension . '/mod_' . $extension . '.xml',
			JPATH_SITE . '/plugins/system/' . $extension . '/' . $extension . '.xml',
			JPATH_SITE . '/plugins/editors-xtd/' . $extension . '/' . $extension . '.xml',
		);

		foreach ($paths as $path)
		{
			if (!JFile::exists($path))
			{
				continue;
			}

			return $path;
		}

		return false;
	}
}
