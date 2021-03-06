<?php
if(!defined('Modular')) die('Direct access not permitted');
?><form
	v-module
	action="users/newService"
	data-done="users/list"
	class="container">
	<h3 v-t>New User</h3>
	<table class="table">
		<tr>
			<td>
				<input type="text" class="form-control" name="username" v-placeholder="Username">
				<span v-text="username"></span>
			</td>
		</tr>
		<tr>
			<td>
				<input type="password" class="form-control" name="password" v-placeholder="Password">
				<span v-text="password"></span>
			</td>
		</tr>
		<tr>
			<td>
				<input type="text" class="form-control" name="email" v-placeholder="Email">
				<span v-text="email"></span>
			</td>
		</tr>
	</table>
	<button type="submit" class="btn btn-success" v-t>Submit</button>
</form>