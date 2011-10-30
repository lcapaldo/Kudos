<div class="ItemLikes">
<?php  if($this->ItemLoves) { ?>
<p class="loves"><span><?= T('Loves') ?>:</span>
<?php $s = null; foreach($this->ItemLoves as $ID => $Name) $s .= '<a href="'.Url('profile/'.$ID.'/'.$Name).'">'.$Name.'</a>, ';
echo trim($s, ', ');
?>
</p>
<?php } ?>
<?php  if($this->ItemHates) { ?>
<p class="hates"><span><?= T('Hates') ?>:</span>
<?php $s = null; foreach($this->ItemHates as $ID => $Name) $s .= '<a href="'.Url('profile/'.$ID.'/'.$Name).'">'.$Name.'</a>, ';
echo trim($s, ', ');
?>
</p>
<?php } ?>
</div>