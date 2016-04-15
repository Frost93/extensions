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

defined('_JEXEC') or die;

/**
 * Plugin that sets the template
 */
class PlgSystemAdvancedTemplatesHelper
{
	var $params       = null;
	var $style_params = array();

	private $_parameters  = null; // will hold the Regular Labs Library parameter object
	private $_assignments = null; // will hold the Regular Labs Library assignments object

	public function __construct(&$params)
	{
		$this->params = $params;
	}

	/*
	 * Replace links to com_modules with com_advancedtemplates
	 */
	function replaceLinks()
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';
		RLFunctions::loadLanguage('com_advancedtemplates');

		if (JFactory::getApplication()->input->get('option') == 'com_templates')
		{
			$this->replaceLinksInCoreTemplateManager();

			return;
		}

		$body = JFactory::getApplication()->getBody();

		$body = preg_replace('#(\?option=com_)(templates[^a-z-_])#', '\1advanced\2', $body);
		$body = str_replace(array('?option=com_advancedtemplates&force=1', '?option=com_advancedtemplates&amp;force=1'), '?option=com_templates', $body);

		JFactory::getApplication()->setBody($body);
	}

	/**
	 *
	 */
	function replaceLinksInCoreTemplateManager()
	{
		if (!$this->params->show_switch)
		{
			return;
		}

		$body = JFactory::getApplication()->getBody();

		$url = 'index.php?option=com_advancedtemplates';
		if (JFactory::getApplication()->input->get('view') == 'style')
		{
			$url .= '&task=style.edit&id=' . (int) JFactory::getApplication()->input->get('id');
		}

		$link = '<a style="float:right;" href="' . JRoute::_($url) . '">' . JText::_('ATP_SWITCH_TO_ADVANCED_TEMPLATE_MANAGER') . '</a><div style="clear:both;"></div>';
		$body = preg_replace('#(</div>\s*</form>\s*(<\!--.*?-->\s*)*</div>)#', $link . '\1', $body);

		JFactory::getApplication()->setBody($body);
	}

	/**
	 *
	 */
	function setTemplate()
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
		$this->_parameters = RLParameters::getInstance();

		if (JFactory::getApplication()->input->get('templateStyle'))
		{
			if (isset($this->params->template_positions_display) && $this->params->template_positions_display)
			{
				return;
			}
		}

		require_once JPATH_LIBRARIES . '/regularlabs/helpers/assignments.php';
		$this->_assignments = new RLAssignmentsHelper;

		$active = $this->getActiveStyle();

		// return if no active template is found
		if (empty($active))
		{
			return;
		}

		// convert params from json to JRegistry object. setTemplate need that.
		$active->params = new JRegistry($active->params);

		JFactory::getApplication()->setTemplate($active->template, $active->params);
	}

	/**
	 * @return Object  The active style
	 */
	function getActiveStyle()
	{
		$styles = $this->getStyles();

		$active = null;

		foreach ($styles as $id => &$style)
		{
			if (!$this->isStyleActive($style, $active))
			{
				continue;
			}

			$active = $style;
			break;
		}

		return $active;
	}

	/**
	 * @return bool  True if the current style should be set as active
	 */
	function isStyleActive(&$style, &$active)
	{
		// continue if default language is already set
		if ($active && $style->home)
		{
			return false;
		}

		// check if style is set as language default
		if ($style->home && $style->home == JFactory::getLanguage()->getTag())
		{
			$active = $style;

			return false;
		}

		// check if style is set as main default
		if ($style->home === 1 || $style->home === '1')
		{
			$active = $style;

			return false;
		}

		// continue if style is set as default for a different language
		if ($style->home)
		{
			return false;
		}

		// continue is style assignments don't pass
		if (!$this->stylePassesAssignments($style))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return Array  An array of template styles with the id as key
	 */
	function getStyles()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('s.*')
			->from('#__template_styles as s')
			->where('s.client_id = 0');
		$db->setQuery($query);

		$styles = $db->loadObjectList('id');

		return $styles;
	}

	/**
	 * @param $style
	 *
	 * @return Object  The advanced parameter object
	 */
	function getStyleParams($style)
	{
		$params = $this->getAdvancedParams($style);

		if (!$this->params->show_assignto_homepage)
		{
			$params->assignto_homepage = 0;
		}
		if (!$this->params->show_assignto_usergrouplevels)
		{
			$params->assignto_usergrouplevels = 0;
		}
		if (!$this->params->show_assignto_date)
		{
			$params->assignto_date = 0;
		}
		if (!$this->params->show_assignto_languages)
		{
			$params->assignto_languages = 0;
		}
		if (!$this->params->show_assignto_templates)
		{
			$params->assignto_templates = 0;
		}
		if (!$this->params->show_assignto_urls)
		{
			$params->assignto_urls = 0;
		}
		if (!$this->params->show_assignto_os)
		{
			$params->assignto_os = 0;
		}
		if (!$this->params->show_assignto_browsers)
		{
			$params->assignto_browsers = 0;
		}
		if (!$this->params->show_assignto_components)
		{
			$params->assignto_components = 0;
		}
		if (!$this->params->show_assignto_tags)
		{
			$params->show_assignto_tags = 0;
		}
		if (!$this->params->show_assignto_content)
		{
			$params->assignto_contentpagetypes = 0;
			$params->assignto_cats             = 0;
			$params->assignto_articles         = 0;
		}

		return $params;
	}

	/**
	 * @param $style
	 *
	 * @return bool
	 */
	function stylePassesAssignments(&$style)
	{
		$params      = $this->getStyleParams($style);
		$assignments = $this->_assignments->getAssignmentsFromParams($params);

		if (!$this->_assignments->hasAssignments($assignments))
		{
			return false;
		}

		return $this->_assignments->passAll($assignments, $params->match_method);
	}

	/**
	 * @param $id
	 *
	 * @return String  The advanced params for the template style in a json string
	 */
	function getAdvancedParams($style)
	{
		if (isset($this->style_params[$style->id]))
		{
			return $this->style_params[$style->id];
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('a.params')
			->from('#__advancedtemplates AS a')
			->where('a.styleid = ' . (int) $style->id);
		$db->setQuery($query);

		$params = $db->loadResult();

		// if no params are found in database, get the default params
		if (empty($params))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_advancedtemplates/models/style.php';
			$model  = new AdvancedTemplatesModelStyle;
			$params = (object) $model->initAssignments($style->id, $style);
		}

		$this->style_params[$style->id] = $this->_parameters->getParams($params, JPATH_ADMINISTRATOR . '/components/com_advancedtemplates/assignments.xml');

		return $this->style_params[$style->id];
	}

	public function changeMenuItemForm($form)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		$name = $form->getName();
		if ($name != 'com_menus.item')
		{
			return true;
		}

		$form->removeField('template_style_id');

		return true;
	}
}
