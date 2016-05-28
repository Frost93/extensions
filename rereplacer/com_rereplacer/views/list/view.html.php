<?php
/**
 * @package         ReReplacer
 * @version         7.0.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/string.php';

jimport('joomla.application.component.view');

/**
 * List View
 */
class ReReplacerViewList extends JViewLegacy
{
	protected $enabled;
	protected $list;
	protected $pagination;
	protected $state;
	protected $config;
	protected $parameters;

	/**
	 * Display the view
	 *
	 */
	public function display($tpl = null)
	{
		require_once JPATH_LIBRARIES . '/regularlabs/helpers/parameters.php';
		$this->parameters = RLParameters::getInstance();

		$this->enabled       = ReReplacerHelper::isEnabled();
		$this->list          = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->config        = $this->parameters->getComponentParams('rereplacer');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->hasCategories = $this->get('HasCategories');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = ReReplacerHelper::getActions();

		$viewLayout = JFactory::getApplication()->input->get('layout', 'default');

		if ($viewLayout == 'import')
		{
			// Set document title
			JFactory::getDocument()->setTitle(JText::_('REREPLACER') . ': ' . JText::_('RL_IMPORT_ITEMS'));
			// Set ToolBar title
			JToolbarHelper::title(JText::_('REREPLACER') . ': ' . JText::_('RL_IMPORT_ITEMS'), 'rereplacer icon-reglab');
			// Set toolbar items for the page
			JToolbarHelper::back();

			return;
		}

		// Set document title
		JFactory::getDocument()->setTitle(JText::_('REREPLACER') . ': ' . JText::_('RL_LIST'));
		// Set ToolBar title
		JToolbarHelper::title(JText::_('REREPLACER') . ': ' . JText::_('RL_LIST'), 'rereplacer icon-reglab');
		// Set toolbar items for the page
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('item.add');
		}
		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('item.edit');
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('list.copy', 'copy', 'copy', 'JTOOLBAR_DUPLICATE', true);
		}
		if ($canDo->get('core.edit.state') && $state->get('filter.state') != 2)
		{
			JToolbarHelper::publish('list.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('list.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($canDo->get('core.delete') && $state->get('filter.state') == -2)
		{
			JToolbarHelper::deleteList('', 'list.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('list.trash');
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('list.export', 'box-remove', 'box-remove', 'RL_EXPORT');
			JToolbarHelper::custom('list.import', 'box-add', 'box-add', 'RL_IMPORT', false);
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_rereplacer');
		}
	}

	function maxlen($string = '', $maxlen = 60)
	{
		if (RLString::strlen($string) > $maxlen)
		{
			$string = RLString::substr($string, 0, $maxlen - 3) . '...';
		}

		return $string;
	}
}
