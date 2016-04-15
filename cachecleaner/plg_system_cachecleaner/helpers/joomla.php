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

require_once __DIR__ . '/cache.php';

class PlgSystemCacheCleanerHelperJoomla extends PlgSystemCacheCleanerHelperCache
{
	public function purge()
	{
		$this->emptyFolder(JPATH_SITE . '/cache');
		$this->emptyFolder(JPATH_ADMINISTRATOR . '/cache');

		$cache = $this->getCache();

		if (!isset($cache->options['storage']) || $cache->options['storage'] == 'file')
		{
			return;
		}

		$cache->clean(null, 'all');
	}

	public function purgeOPcache()
	{
		if (!extension_loaded('Zend OPcache'))
		{
			return;
		}

		opcache_reset();
	}

	public function purgeExpired()
	{
		$cache = $this->getCache();
		$cache->gc();
	}

	public function purgeUpdates()
	{
		$db = JFactory::getDbo();
		$db->setQuery('TRUNCATE TABLE #__updates');
		if (!$db->execute())
		{
			return;
		}

		// Reset the last update check timestamp
		$query = $db->getQuery(true)
			->update('#__update_sites')
			->set('last_check_timestamp = ' . $db->quote(0));
		$db->setQuery($query);
		$db->execute();
	}


	private function getCache()
	{
		$conf = JFactory::getConfig();

		$options = array(
			'defaultgroup' => '',
			'storage'      => $conf->get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => $conf->get('cache_path', JPATH_SITE . '/cache'),
		);

		$cache = JCache::getInstance('', $options);

		return $cache;
	}
}
