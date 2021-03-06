<?php
if(!defined('Modular')) die('Direct access not permitted');

use Enpowi\App;
use Enpowi\Users\Group;
use Enpowi\Users\User;
use Enpowi\Modules\DataOut;

$id = (new DataOut())
	->add('user', $user = new User(App::param('username')))
	->add('groups', Group::editableGroups())
	->bind();

?><form
	listen
	v-module
	data="<?php echo $id ?>"
	action="group/ofUserService"
	class="container">

	<h3>
		<span v-t>Groups for: </span>
		{{ user.username }}
	</h3>

	<input
		name="user"
		type="hidden"
		value="{{ stringify( user ) }}">

	<div v-repeat=" group : groups ">

		<input
			v-module-item
			name="groups[]"
			type="checkbox"
			value="{{ stringify( group ) }}"
		    v-model=" user.groups[ $key ] ">

		<label>{{ group.name }}</label>
	</div>
</form>