<?php
/**
 * @package         Tabs
 * @version         6.0.3
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemTabsHelperHead
{
	var $helpers = array();
	var $params  = null;

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemTabsHelpers::getInstance();
		$this->params  = $this->helpers->getParams();
	}

	public function addHeadStuff()
	{
		// do not load scripts/styles on feeds or print pages
		if (RLFunctions::isFeed() || JFactory::getApplication()->input->getInt('print', 0))
		{
			return;
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

		if ($this->params->load_bootstrap_framework)
		{
			JHtml::_('bootstrap.framework');
		}


		$script = '
			var rl_tabs_use_hash = ' . (int) $this->params->use_hash . ';
			var rl_tabs_reload_iframes = ' . (int) $this->params->reload_iframes . ';
			var rl_tabs_init_timeout = ' . (int) $this->params->init_timeout . ';
		';
		JFactory::getDocument()->addScriptDeclaration('/* START: Tabs scripts */ ' . preg_replace('#\n\s*#s', ' ', trim($script)) . ' /* END: Tabs scripts */');

		RLFunctions::script('tabs/script.min.js', ($this->params->media_versioning ? '6.0.3' : false));

		if ($this->params->load_stylesheet)
		{
			RLFunctions::stylesheet('tabs/style.min.css', ($this->params->media_versioning ? '6.0.3' : false));
		}

	}

	public function removeHeadStuff(&$html)
	{
		// Don't remove if tabs class is found
		if (strpos($html, 'class="rl_tabs-tab') !== false)
		{
			return;
		}

		// remove style and script if no items are found
		$html = preg_replace('#\s*<' . 'link [^>]*href="[^"]*/(tabs/css|css/tabs)/[^"]*\.css[^"]*"[^>]*( /)?>#s', '', $html);
		$html = preg_replace('#\s*<' . 'script [^>]*src="[^"]*/(tabs/js|js/tabs)/[^"]*\.js[^"]*"[^>]*></script>#s', '', $html);
		$html = preg_replace('#((?:;\s*)?)(;?)/\* START: Tabs .*?/\* END: Tabs [a-z]* \*/\s*#s', '\1', $html);
	}
}
