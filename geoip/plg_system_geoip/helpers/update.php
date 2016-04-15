<?php
/**
 * @package         GeoIp
 * @version         1.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
RLFunctions::loadLanguage('plg_system_geoip');

$updater = new GeoIPUpdater;

if ($message = $updater->update())
{
	$prefix = strpos($message, 'ERROR') === false ? '+' : '';;
	$message = $prefix . JText::_('GEO_MESSAGE_' . $message);

	die($message);
}

if (!$last_date = $updater->getVersion())
{
	die();
}

$message = '+' . JText::sprintf('GEO_MESSAGE_UPDATED_TO', JHtml::_('date', $last_date, JText::_('DATE_FORMAT_LC3')));

die($message);

class GeoIPUpdater
{
	public function update($name = 'City')
	{
		jimport('joomla.filesystem.file');

		$this->database_name = 'GeoLite2-' . $name . '.mmdb';
		$this->package       = JFactory::getConfig()->get('tmp_path') . '/' . $this->database_name . '.gz';
		$this->database      = JPATH_LIBRARIES . '/geoip/' . $this->database_name;
		$this->date_file     = $this->database . '.date.txt';
		$this->force         = JFactory::getApplication()->input->getInt('force');

		if ($error = $this->download())
		{
			return $error;
		}

		if ($error = $this->unpack())
		{
			JFile::delete($this->package);

			return $error;
		}

		JFile::delete($this->package);

		return '';
	}

	public function getVersion()
	{
		if (!is_file($this->date_file))
		{
			return 0;
		}

		return file_get_contents($this->date_file);
	}

	private function download()
	{
		$last_date = $this->getVersion();

		if (!$this->force && $last_date && date('Y-m') == date('Y-m', $last_date))
		{
			return 'UPTODATE';
		}

		$package = JHttpFactory::getHttp()->get('http://geolite.maxmind.com/download/geoip/database/' . $this->database_name . '.gz', null, 30);

		if (!$package || $package->code != 200 || empty($package->body))
		{
			return 'ERROR_DOWNLOAD';
		}

		$date = strtotime($package->headers['Last-Modified']);

		if (!$this->force && $last_date && $date <= $last_date)
		{
			return 'UPTODATE';
		}

		JFile::write($this->package, $package->body);
		JFile::write($this->date_file, $date);

		return;
	}

	private function unpack()
	{
		$result = JArchive::getAdapter('gzip')->extract($this->package, $this->database);

		if ($result instanceof Exception)
		{
			return 'ERROR_UNPACK';
		}

		return;
	}
}
