<div class="users form">
<?php echo $this->Form->create('User');?>
	<fieldset>
		<legend><?php echo __('Edit User'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('username');
		echo $this->Form->input('password');
		echo $this->Form->input('role_id');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('User.id')), null, __('Are you sure you want to delete # %s?', $this->Form->value('User.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Users'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Commits'), array('controller' => 'commits', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Commit'), array('controller' => 'commits', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List User Emails'), array('controller' => 'user_emails', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New User Email'), array('controller' => 'user_emails', 'action' => 'add')); ?> </li>
	</ul>
</div>
