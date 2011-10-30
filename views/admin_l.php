<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Info">
   <?php echo T('Kudos options.'); ?>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
	<li>
		<div class="Info">Kudos administration panel</div>
	</li>
	<li>
		<?php
		$Attributes = array("value"=> 1);
		if( C('Plugins.Kudos.Enable') ) $Attributes['checked'] = 'checked';
		echo $this->Form->CheckBox('Kudoers', "Enable Kudoers list?", array("value" => 1));
		?>
	</li>
	<li>
		<?php
		$Attributes = array("value"=> 1);
		if( C('Plugins.Kudos.Delete') ) $Attributes['checked'] = 'checked';
		echo $this->Form->CheckBox('KudosDelete', "Enable Comment deleting via Kudos?", $Attributes);
		?>
	</li>
	<li>
		<?php
		echo $this->Form->Label('Negative Kudos', 'Negative Kudos');
		echo "<span>(number of negative kudos for comments deleting)</span>";
		echo $this->Form->TextBox('KudosDeleteNumber', array("value" => C('Plugins.Kudos.DeleteNumber')));
		?>
	</li>
</ul>
<?php echo $this->Form->Close('Save'); ?>
