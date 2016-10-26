<?php
/**
 * [ COPYRIGHT HEADER ]
 */

defined('_JEXEC') or die;
?>
<div id="<?php echo $displayData['id']; ?>" class="contenttemplater-list">
	<ul role="menu" class="dropdown-menu">
		<li>
			<?php echo implode('</li><li>', $displayData['options']); ?>
		</li>
	</ul>
</div>
