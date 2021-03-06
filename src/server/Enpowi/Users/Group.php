<?php
/**
 * Created by PhpStorm.
 * User: robert
 * Date: 2/26/15
 * Time: 5:13 PM
 */

namespace Enpowi\Users;

use R;
use Respect\Validation\Validator as v;

class Group {

	public $name;
	public $perms;
	public $isSystem = false;

	private $_bean;

	public function __construct($name, $bean = null)
	{
		$this->name = $name;

		if ($bean === null) {
			$this->_bean = $bean = R::findOne( 'group', ' name = ? ', [ $name ] );
		} else {
			$this->_bean = $bean;
		}

		if (
			$bean !== null
			&& (
				$bean->isDefaultRegistered
				|| $bean->isDefaultAnonymous
				|| $bean->isEveryone
				|| $bean->isSuper
			)
		) {
			$this->isSystem = true;
		}
	}

	public static function getWithPermissions($name, $bean = null)
	{
		$group = new self($name, $bean);
		$group->updatePerms();
		return $group;
	}

	public static function create($groupName, $isDefaultRegistered = false, $isDefaultAnonymous = false, $isEveryone = false, $isSuper = false)
	{
		$count = R::count( 'group', ' name = ? ', [ $groupName ] );

		if ($count < 1) {
			$bean = R::dispense('group');
			$bean->name = $groupName;
			$bean->isDefaultRegistered = $isDefaultRegistered;
			$bean->isDefaultAnonymous = $isDefaultAnonymous;
			$bean->isEveryone = $isEveryone;
			$bean->isSuper = $isSuper;
			$bean->ownUserList;
			$bean->sharedPermList;

			$id = R::store($bean);

			return new Group($groupName, $bean);
		}

		return null;
	}

	public function id()
	{
		return $this->_bean->getID();
	}

	public function remove()
	{
		$bean = R::findOne( 'group', ' name = ? ', [ $this->name ] );

		if ($bean !== null) {
			R::trash($bean);
			return true;
		}
		return false;
	}

	public function addUser(User $user)
	{
		$userBean = $user->bean();
		$groupBean = $this->_bean;

		if ($groupBean !== null) {
			$userBean->sharedGroupList[] = $groupBean;

			R::store($userBean);

			$user->updateGroups();

			return true;
		}
		return false;
	}

	public function removeUser(User $user)
	{
		$userBean = $user->bean();
		$groupBean = $this->_bean;

		if ($groupBean !== null && $user !== null) {

			if (
				!$groupBean->isDefaultRegistered
				&& !$groupBean->isDefaultAnonymous
				&& !$groupBean->isEveryone
			) {
				unset($userBean->sharedGroupList[$groupBean->getID()]);

				R::store($userBean);

				$user->updateGroups();
			}

			return false;
		}
		return true;
	}

	public function countUsers()
	{
		return R::count( 'group', ' name = ? ', [ $this->name ] );
	}

	public function users()
	{
		$groupBean = $this->_bean;
		$users = [];
		foreach($groupBean->sharedUserList as $userBean) {
			$users[] = new User($userBean->username, $userBean);
		}
		return $users;
	}

	public static function groups()
	{
		$groups = [];

		foreach(R::find('group') as $groupBean) {
			$groups[] = new Group($groupBean->name, $groupBean);
		}

		return $groups;
	}

	public static function editableGroups($updatePerms = false, $excludeSuper = false)
	{
		$groups = [];

		if ($excludeSuper) {
			$sqlLookup = ' is_default_anonymous = 0 and is_default_registered = 0 and is_everyone = 0 and is_super = 0 ';
		} else {
			$sqlLookup = ' is_default_anonymous = 0 and is_default_registered = 0 and is_everyone = 0 ';
		}

		foreach(R::find('group', $sqlLookup) as $groupBean) {
			$group = new Group( $groupBean->name, $groupBean );

			$groups[$groupBean->id] = $group;

			if ($updatePerms) {
				$group->updatePerms();
			}
		}

		return $groups;
	}

	public static function isValidGroupName($groupName)
	{
		return v::alnum()
			->noWhitespace()
			->length(3,200)
			->validate($groupName);
	}

	public function bean()
	{
		return $this->_bean;
	}

	public function ensureExists()
	{
		if ($this->_bean === null) {
			$this->_bean = R::findOne('group', ' name = ? ', [$this->name]);
		}

		return $this;
	}

	public function updatePerms()
	{
		$perms = [];
		$permBeans = R::findAll('perm', ' group_name = ? ', [$this->name]);

		foreach($permBeans as $permBean) {
			$perms[$permBean->module . '/' . $permBean->component] = new Perm($permBean->module, $permBean->component, $this);
		}

		$this->perms = $perms;

		return $this;
	}

	public function removePerms()
	{
		if ($this->name !== 'Administrator') {
			$beans = R::findAll('perm', ' group_name = ? ', [$this->name]);

			R::trashAll($beans);
		}

		return $this;
	}
}