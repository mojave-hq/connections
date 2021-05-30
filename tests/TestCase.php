<?php

namespace MojaveHQ\Friends\Tests;

use MojaveHQ\Friends\FriendshipServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	protected function getPackageProviders($app)
	{
		return [
			FriendshipServiceProvider::class,
		];
	}

	protected function getEnvironmentSetUp($app)
	{
		include_once __DIR__ . '/../database/migrations/create_friends_pivot_table.php.stub';
		include_once __DIR__ . '/../database/migrations/create_users_table.php.stub';

		(new \CreateFriendsPivotTable)->up();
		(new \CreateUsersTable)->up();
	}
}