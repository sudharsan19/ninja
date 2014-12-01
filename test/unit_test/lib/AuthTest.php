<?php
require_once ("op5/objstore.php");
require_once ("op5/config.php");
require_once ("op5/auth/Auth.php");

/**
 * Verifies that auth driver handles sessions correctly
 */
class AuthTest extends PHPUnit_Framework_TestCase {
	private static $config = array (
		'auth' => array (
			'common' => array (
				'default_auth' => 'mydefault',
				'session_key' => 'testkey'
			),
			'mydefault' => array (
				'driver' => 'Default'
			)
		),
		'auth_users' => array (
			'user_plain' => array (
				'username' => 'user_plain',
				'realname' => 'Just a plain user',
				'password' => 'user_pass',
				'password_algo' => 'plain',
				'modules' => array (
					'mydefault'
				),
				'groups' => array (
					'plain_group'
				)
			),
			'user_nomodule' => array (
				'username' => 'user_nomodule',
				'realname' => 'User that hasn\'t got access to any module',
				'password' => 'user_pass',
				'password_algo' => 'plain',
				'modules' => array (),
				'groups' => array (
					'plain_group'
				)
			)
		),
		'auth_groups' => array (
			'plain_group' => array (
				'some_plain_access'
			)
		),
		'log' => array ()
	);

	/**
	 * Confirm that we start from scratch
	 */
	static public function setUpBeforeClass() {
		op5objstore::instance()->mock_clear();
		op5objstore::instance()->mock_add('op5config',
			new MockConfig(self::$config));
	}

	/**
	 * Make sure we clear everything up.
	 */
	public static function tearDownAfterClass() {
		op5objstore::instance()->mock_clear();
		op5objstore::instance()->clear();
	}

	/**
	 * Make sure we don't have any lasting instances between tests
	 */
	public function setup() {
		op5objstore::instance()->clear();
		/* Start with a clean session */
		$_SESSION = array ('this_should_be_untouched' => 17);
	}

	/**
	 * Test a simple login cycle with login and logout, and all intermediate
	 * states
	 */
	public function test_login_logout() {
		$auth = new op5auth();

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());

		/* Session should be written during login */
		$auth->login('user_plain', 'user_pass');

		$this->assertEquals(
			array (
				'testkey' => array (
					'username' => 'user_plain',
					'realname' => 'Just a plain user',
					'email' => false,
					'auth_data' => array (
						'own_user_change_password' => true,
						'some_plain_access' => true
					),
					'modules' => array (
						'mydefault'
					),
					'auth_method' => 'mydefault',
					'groups' => array (
						'plain_group'
					)
				),
				'this_should_be_untouched' => 17
			), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'user_plain',
				'realname' => 'Just a plain user',
				'email' => false,
				'authorized' => array (
					'own_user_change_password' => true,
					'some_plain_access' => true
				),
				'groups' => array (
					'plain_group'
				)
			), $auth->getActorInfo());

		/* Session should be emptied */
		$auth->logout();

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());
	}

	/**
	 * Test a login cycle, but close the session handling, so just the auth
	 * library is logged out, but the session remains
	 */
	public function test_login_close_logout() {
		$auth = new op5auth();

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());

		/* Session should be written during login */
		$auth->login('user_plain', 'user_pass');

		$this->assertEquals(
			array (
				'testkey' => array (
					'username' => 'user_plain',
					'realname' => 'Just a plain user',
					'email' => false,
					'auth_data' => array (
						'own_user_change_password' => true,
						'some_plain_access' => true
					),
					'modules' => array (
						'mydefault'
					),
					'auth_method' => 'mydefault',
					'groups' => array (
						'plain_group'
					)
				),
				'this_should_be_untouched' => 17
			), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'user_plain',
				'realname' => 'Just a plain user',
				'email' => false,
				'authorized' => array (
					'own_user_change_password' => true,
					'some_plain_access' => true
				),
				'groups' => array (
					'plain_group'
				)
			), $auth->getActorInfo());

		/* Session shouldn't be emptied */
		$auth->write_close();
		$auth->logout();

		$this->assertEquals(
			array (
				'testkey' => array (
					'username' => 'user_plain',
					'realname' => 'Just a plain user',
					'email' => false,
					'auth_data' => array (
						'own_user_change_password' => true,
						'some_plain_access' => true
					),
					'modules' => array (
						'mydefault'
					),
					'auth_method' => 'mydefault',
					'groups' => array (
						'plain_group'
					)
				),
				'this_should_be_untouched' => 17
			), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());
	}

	/**
	 * Test a one-shot login cycle, by closing the session before logging in and
	 * out.
	 */
	public function test_close_login_logout() {
		$auth = new op5auth();

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());

		/* Session shouldn't be written during login, but user is logged in */
		$auth->write_close();
		$auth->login('user_plain', 'user_pass');

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'user_plain',
				'realname' => 'Just a plain user',
				'email' => false,
				'authorized' => array (
					'own_user_change_password' => true,
					'some_plain_access' => true
				),
				'groups' => array (
					'plain_group'
				)
			), $auth->getActorInfo());

		/* Session should still be emptied */
		$auth->logout();

		$this->assertEquals(array ('this_should_be_untouched' => 17), $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => false,
				'name' => 'notauthenticated',
				'realname' => 'Not Logged in',
				'email' => false,
				'authorized' => array (),
				'groups' => array ()
			), $auth->getActorInfo());
	}

	/**
	 * Test restoring the user from the session, preserving a logged in user.
	 */
	public function test_user_load() {
		/* Mock up a user */
		$_SESSION = array (
			'testkey' => array (
				'username' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'auth_data' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'modules' => array (
					'mydefault'
				),
				'auth_method' => 'mydefault',
				'groups' => array (
					'advanced_group_stuff'
				)
			),
			'this_should_be_untouched' => 17
		);

		$original_session = $_SESSION;

		/* User should be loaded, session untouched */
		$auth = new op5auth();

		$this->assertEquals($original_session, $_SESSION);
		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'authorized' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'groups' => array (
					'advanced_group_stuff'
				)
			), $auth->getActorInfo());
	}

	/**
	 * Test forcing a user, which means bypassing the auth mechanism.
	 * It should still write to the session
	 */
	public function test_force_user_update_session() {
		/* Mock up a user */
		$_SESSION = array (
			'testkey' => array (
				'username' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'auth_data' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'modules' => array (
					'mydefault'
				),
				'auth_method' => 'mydefault',
				'groups' => array (
					'advanced_group_stuff'
				)
			),
			'this_should_be_untouched' => 17
		);

		/* User should be loaded */
		$auth = new op5auth();

		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'authorized' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'groups' => array (
					'advanced_group_stuff'
				)
			), $auth->getActorInfo());

		$auth->force_user(
			new op5User(
				array (
					'username' => 'a_forced_user',
					'realname' => 'This is forced',
					'email' => 'i_do_have@n.email',
					'auth_data' => array (
						'i_can_do_other_stuff' => true
					),
					'groups' => array (
						'somestuff'
					)
				)), false);

		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'a_forced_user',
				'realname' => 'This is forced',
				'email' => 'i_do_have@n.email',
				'authorized' => array (
					'i_can_do_other_stuff' => true
				),
				'groups' => array (
					'somestuff'
				)
			), $auth->getActorInfo());

		/* Session should be untouched */
		$this->assertEquals(
			array (
				'testkey' => array (
					'username' => 'a_forced_user',
					'realname' => 'This is forced',
					'email' => 'i_do_have@n.email',
					'auth_data' => array (
						'i_can_do_other_stuff' => true
					),
					'groups' => array (
						'somestuff'
					)
				),
				'this_should_be_untouched' => 17
			), $_SESSION);
	}

	/**
	 * Test forcing a user, after closing the session.
	 * The session should be totally untouched, but a new temporary user should
	 * be used
	 */
	public function test_close_force_user_keep_session() {
		/* Mock up a user */
		$_SESSION = array (
			'testkey' => array (
				'username' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'auth_data' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'modules' => array (
					'mydefault'
				),
				'auth_method' => 'mydefault',
				'groups' => array (
					'advanced_group_stuff'
				)
			),
			'this_should_be_untouched' => 17
		);

		$original_session = $_SESSION;

		/* User should be loaded */
		$auth = new op5auth();

		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'from_session_user',
				'realname' => 'A user from the session',
				'email' => 'i_have@an.email',
				'authorized' => array (
					'i_can_do_stuff' => true,
					'some_advanced_access' => true
				),
				'groups' => array (
					'advanced_group_stuff'
				)
			), $auth->getActorInfo());

		$auth->write_close();
		$auth->force_user(
			new op5User(
				array (
					'type' => 'user',
					'authenticated' => true,
					'username' => 'a_forced_user',
					'realname' => 'This is forced',
					'email' => 'i_do_have@n.email',
					'auth_data' => array (
						'i_can_do_other_stuff' => true
					),
					'groups' => array (
						'somestuff'
					)
				)), false);

		$this->assertEquals(
			array (
				'type' => 'user',
				'authenticated' => true,
				'name' => 'a_forced_user',
				'realname' => 'This is forced',
				'email' => 'i_do_have@n.email',
				'authorized' => array (
					'i_can_do_other_stuff' => true
				),
				'groups' => array (
					'somestuff'
				)
			), $auth->getActorInfo());

		/* Session should be untouched */
		$this->assertEquals($original_session, $_SESSION);
	}
}