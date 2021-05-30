<?php

namespace MojaveHQ\Friends;

use Illuminate\Support\ServiceProvider;

class FriendshipServiceProvider extends ServiceProvider
{
	public function register()
	{
        $this->mergeConfigFrom(__DIR__.'/../config/friends.php', 'friends');
	}

	public function boot()
	{
        if ($this->app->runningInConsole()) {

            $stub = __DIR__ . '../database/migrations/';
            $target = database_path('migrations') . '/';

            if (! class_exists('CreateFriendsPivotTable')) {
                $this->publishes([
                    $stub . 'create_friends_pivot_table.php.stub' => $target . date('Y_m_d_His', time()) . '_create_friends_pivot_table.php',
                ], 'migrations');
            }

            $this->publishes([
                __DIR__ . '../config/friends.php' => config_path('friends.php'),
            ], 'config');
        }
	}
}