<?php
/**
 * [ COPYRIGHT HEADER ]
 */

defined('_JEXEC') or die;
?>

<a class="hasPopover"
   data-trigger="hover"
   title="<?php echo $displayData['text']; ?>"
   data-content="<?php echo $displayData['description']; ?>"
   href="javascript:;"
   onclick="<?php echo $displayData['onclick']; ?>"
>
	<?php echo $displayData['image'] . $displayData['text']; ?>
</a>
