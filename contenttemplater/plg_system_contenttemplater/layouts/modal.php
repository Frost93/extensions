<?php
/**
 * [ COPYRIGHT HEADER ]
 */

defined('_JEXEC') or die;

$filter_category = JFactory::getApplication()->input->get('catid');
?>
<div id="<?php echo $displayData['form_id']; ?>" tabindex="-1" class="contenttemplater-modal">

	<h3><?php echo JText::_('INSERT_TEMPLATE'); ?></h3>

	<div class="row-fluid">

		<?php if (!empty($displayData['categories'])) : ?>
			<form class="float-right" action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
				<select name="catid" onchange="document.adminForm.submit();">
					<option value="">-- <?php echo JText::_('JCATEGORY'); ?> --</option>
					<?php foreach ($displayData['categories'] as $cat) : ?>
						<option value="<?php echo $cat; ?>"<?php echo $filter_category == $cat ? ' selected="selected"' : ''; ?>>
							<?php echo $cat; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
		<?php endif; ?>

		<ul class="list list-striped">
			<li>
				<?php echo implode('</li><li>', $displayData['options']); ?>
			</li>
		</ul>

	</div>

	<?php echo $displayData['footer']; ?>
</div>
