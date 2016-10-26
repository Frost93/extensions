<?php
/**
 * @package         Content Templater
 * @version         6.2.6
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/functions.php';

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

RLFunctions::stylesheet('regularlabs/style.min.css');
RLFunctions::stylesheet('contenttemplater/style.min.css', '6.2.6');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$ordering  = ($listOrder == 'a.ordering');

$editor = JFactory::getEditor();

$user       = JFactory::getUser();
$canCreate  = $user->authorise('core.create', 'com_contenttemplater');
$canEdit    = $user->authorise('core.edit', 'com_contenttemplater');
$canChange  = $user->authorise('core.edit.state', 'com_contenttemplater');
$canCheckin = $user->authorise('core.manage', 'com_checkin');
$saveOrder  = ($listOrder == 'a.ordering');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_contenttemplater&task=list.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'itemList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$cols = 7;
$cols += ($this->hasCategories ? 1 : 0);

// Version check
require_once JPATH_LIBRARIES . '/regularlabs/helpers/versions.php';
if ($this->config->show_update_notification)
{
	echo RLVersions::render('CONTENT_TEMPLATER');
}
?>
	<form action="<?php echo JRoute::_('index.php?option=com_contenttemplater&view=list'); ?>" method="post" name="adminForm" id="adminForm">
		<?php
		// Search tools bar
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>

		<table class="table table-striped" id="itemList">
			<thead>
				<tr>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
					</th>
					<th width="1%" class="hidden-phone">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
					</th>
					<th class="title">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th class="title hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_DESCRIPTION', 'a.description', $listDirn, $listOrder); ?>
					</th>
					<?php if ($this->hasCategories) : ?>
						<th width="5%" class="nowrap left hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JCATEGORY', 'a.category', $listDirn, $listOrder); ?>
						</th>
					<?php endif; ?>
					<th width="5%" class="nowrap center hidden-phone">
						<?php echo JText::_('RL_FRONTEND'); ?>
					</th>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo $cols; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php if (empty($this->list)): ?>
					<tr>
						<td colspan="<?php echo $cols; ?>">
							<?php echo JText::_('RL_NO_ITEMS_FOUND'); ?>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($this->list as $i => $item) :
						$canCheckinItem = ($canCheckin || $item->checked_out == 0 || $item->checked_out == $user->get('id'));
						$canChangeItem = ($canChange && $canCheckinItem);

						if ($item->button_enable_in_frontend)
						{
							$enable_in_frontend = '<span class="btn btn-micro disabled" rel="tooltip" title="' . JText::_('RL_ENABLE_IN_FRONTEND') . '"><span class="icon-publish"></span></a>';
						}
						else
						{
							$enable_in_frontend = '<span class="btn btn-micro disabled" rel="tooltip" title="' . JText::_('RL_NOT') . ' ' . JText::_('RL_ENABLE_IN_FRONTEND') . '"><span class="icon-cancel"></span></a>';
						}
						?>
						<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->category ?: 'no-group'; ?>">
							<td class="order nowrap center hidden-phone">
								<?php if ($canChange) :
									$disableClassName = '';
									$disabledLabel = '';
									if (!$saveOrder) :
										$disabledLabel    = JText::_('JORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									endif; ?>
									<span class="sortable-handler <?php echo $disableClassName ?>" rel="tooltip" title="<?php echo $disabledLabel ?>">
										<span class="icon-menu"></span>
									</span>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
									       class="width-20 text-area-order">
								<?php else : ?>
									<span class="sortable-handler inactive">
										<span class="icon-menu"></span>
									</span>
								<?php endif; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo JHtml::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center center">
								<?php echo JHtml::_('jgrid.published', $item->published, $i, 'list.', $canChangeItem); ?>
							</td>
							<td>
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $editor, $item->checked_out_time, 'list.', $canCheckin); ?>
								<?php endif; ?>

								<?php echo getItemImage($item); ?>

								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_contenttemplater&task=item.edit&id=' . $item->id); ?>">
										<?php echo $this->escape(str_replace(JUri::root(), '', $item->name)); ?></a>
								<?php else : ?>
									<?php echo $this->escape(str_replace(JUri::root(), '', $item->name)); ?>
								<?php endif; ?>
							</td>
							<td class="hidden-phone">
								<?php
								$description = explode('---', $item->description);
								$descr       = nl2br($this->escape(trim($description['0'])));
								if (isset($description['1']))
								{
									$descr = '<span rel="tooltip" title="' . makeTooltipSafe(trim($description['1'])) . '">' . $descr . '</span>';
								}
								echo $descr;
								?>
							</td>
							<?php if ($this->hasCategories) : ?>
								<td class="left hidden-phone">
									<?php
									$category      = $item->category;
									$category_icon = '';

									if (strpos($category, '::'))
									{
										list($category, $category_icon) = explode('::', $category, 2);
										$category_icon = '<span class="icon-' . $category_icon . '"></span> ';
									}

									echo $category ? '<span class="label label-default">' . $category_icon . $category . '</span>' : '';
									?>
								</td>
							<?php endif; ?>
							<td class="center hidden-phone">
								<?php echo $enable_in_frontend; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<input type="hidden" name="task" value="">
		<input type="hidden" name="boxchecked" value="0">
		<?php echo JHtml::_('form.token'); ?>
	</form>

<?php
// PRO Check
require_once JPATH_LIBRARIES . '/regularlabs/helpers/licenses.php';
echo RLLicenses::render('CONTENT_TEMPLATER');

// Copyright
echo RLVersions::getFooter('CONTENT_TEMPLATER');

function makeTooltipSafe($str)
{
	return str_replace(
		array('"', '::', "&lt;", "\n"),
		array('&quot;', '&#58;&#58;', "&amp;lt;", '<br>'),
		htmlentities(trim($str), ENT_QUOTES, 'UTF-8')
	);
}

function getItemImage($item)
{
	if (empty($item->button_image))
	{
		return '';
	}

	// template should be displayed as a button
	$icon = str_replace('.png', '', $item->button_image);

	if ($icon == -1)
	{
		return '';
	}

	return '<span class="icon-' . $icon . '"></span>';
}
