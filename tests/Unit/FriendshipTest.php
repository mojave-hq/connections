<?php

namespace MojaveHQ\Friends\Tests\Feature;

use MojaveHQ\Friends\Tests\User;
use MojaveHQ\Friends\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    protected $sender;

    protected $recipient;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->sender = User::factory()->create();
        $this->recipient = User::factory()->create();
    }

    /** @test */
    public function it_returns_all_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $this->assertCount(3, $this->sender->getAllFriendships());
    }
    
    /** @test */
    public function it_returns_number_of_accepted_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $this->assertEquals(2, $this->sender->getFriendsCount());
    }
    
    /** @test */
    public function it_returns_accepted_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $this->assertCount(2, $this->sender->getAcceptedFriendships());
    }
    
    /** @test */
    public function it_returns_only_accepted_user_friendships()
    {
        $recipients = User::factory()->count(4)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $this->assertCount(2, $this->sender->getAcceptedFriendships());
        
        $this->assertCount(1, $recipients[0]->getAcceptedFriendships());
        $this->assertCount(1, $recipients[1]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[2]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[3]->getAcceptedFriendships());
    }
    
    /** @test */
    public function it_returns_pending_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $this->assertCount(2, $this->sender->getPendingFriendships());
    }
    
    /** @test */
    public function it_returns_denied_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $this->assertCount(1, $this->sender->getDeniedFriendships());
    }
    
    /** @test */
    public function it_returns_blocked_user_friendships()
    {
        $recipients = User::factory()->count(3)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->blockFriend($this->sender);
        $this->assertCount(1, $this->sender->getBlockedFriendships());
    }
    
    /** @test */
    public function it_returns_user_friends()
    {
        $recipients = User::factory()->count(4)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        
        $this->assertCount(2, $this->sender->getFriends());
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[3]->getFriends());
        
        $this->containsOnlyInstancesOf(User::class, $this->sender->getFriends());
    }
    
    /** @test */
    public function it_returns_user_friends_per_page()
    {
        $recipients = User::factory()->count(6)->create();
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
        }
        
        $recipients[0]->acceptFriendRequest($this->sender);
        $recipients[1]->acceptFriendRequest($this->sender);
        $recipients[2]->denyFriendRequest($this->sender);
        $recipients[3]->acceptFriendRequest($this->sender);
        $recipients[4]->acceptFriendRequest($this->sender);
        
        
        $this->assertCount(2, $this->sender->getFriends(2));
        $this->assertCount(4, $this->sender->getFriends(0));
        $this->assertCount(4, $this->sender->getFriends(10));
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[5]->getFriends(2));
        
        $this->containsOnlyInstancesOf(User::class, $this->sender->getFriends());
    }
    
    /** @test */
    public function it_returns_user_friends_of_friends()
    {
        $recipients = User::factory()->count(2)->create();
        $friendsOfFriends = User::factory()->count(5)->create()->chunk(3);
        
        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
            $recipient->acceptFriendRequest($this->sender);

            foreach ($friendsOfFriends->shift() as $friendOfFriend) {
                $recipient->befriend($friendOfFriend);
                $friendOfFriend->acceptFriendRequest($recipient);
            }
        }
        
        $this->assertCount(2, $this->sender->getFriends());
        $this->assertCount(4, $recipients[0]->getFriends());
        $this->assertCount(3, $recipients[1]->getFriends());
        
        $this->assertCount(5, $this->sender->getFriendsOfFriends());
        
        $this->containsOnlyInstancesOf(User::class, $this->sender->getFriendsOfFriends());
    }

    /** @test */
    public function it_returns_user_mutual_friends()
    {
        $recipients = User::factory()->count(2)->create();
        $friendsOfFriends = User::factory()->count(5)->create()->chunk(3);

        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
            $recipient->acceptFriendRequest($this->sender);

            foreach ($friendsOfFriends->shift() as $friendOfFriend) {
                $recipient->befriend($friendOfFriend);
                $friendOfFriend->acceptFriendRequest($recipient);
                $friendOfFriend->befriend($this->sender);
                $this->sender->acceptFriendRequest($friendOfFriend);
            }
        }

        $this->assertCount(3, $this->sender->getMutualFriends($recipients[0]));
        $this->assertCount(3, $recipients[0]->getMutualFriends($this->sender));

        $this->assertCount(2, $this->sender->getMutualFriends($recipients[1]));
        $this->assertCount(2, $recipients[1]->getMutualFriends($this->sender));

        $this->containsOnlyInstancesOf(User::class, $this->sender->getMutualFriends($recipients[0]));
    }

    /** @test */
    public function it_returns_user_mutual_friends_per_page()
    {
        $recipients = User::factory()->count(2)->create();
        $friendsOfFriends = User::factory()->count(8)->create()->chunk(5);

        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
            $recipient->acceptFriendRequest($this->sender);

            foreach ($friendsOfFriends->shift() as $friendOfFriend) {
                $recipient->befriend($friendOfFriend);
                $friendOfFriend->acceptFriendRequest($recipient);
                $friendOfFriend->befriend($this->sender);
                $this->sender->acceptFriendRequest($friendOfFriend);
            }
        }

        $this->assertCount(2, $this->sender->getMutualFriends($recipients[0], 2));
        $this->assertCount(5, $this->sender->getMutualFriends($recipients[0], 0));
        $this->assertCount(5, $this->sender->getMutualFriends($recipients[0], 10));
        $this->assertCount(2, $recipients[0]->getMutualFriends($this->sender, 2));
        $this->assertCount(5, $recipients[0]->getMutualFriends($this->sender, 0));
        $this->assertCount(5, $recipients[0]->getMutualFriends($this->sender, 10));

        $this->assertCount(1, $recipients[1]->getMutualFriends($recipients[0], 10));

        $this->containsOnlyInstancesOf(\App\User::class, $this->sender->getMutualFriends($recipients[0], 2));
    }

    /** @test */
    public function it_returns_number_of_user_mutual_friends()
    {
        $recipients = User::factory()->count(2)->create();
        $friendsOfFriends = User::factory()->count(5)->create()->chunk(3);

        foreach ($recipients as $recipient) {
            $this->sender->befriend($recipient);
            $recipient->acceptFriendRequest($this->sender);

            foreach ($friendsOfFriends->shift() as $friendOfFriend) {
                $recipient->befriend($friendOfFriend);
                $friendOfFriend->acceptFriendRequest($recipient);
                $friendOfFriend->befriend($this->sender);
                $this->sender->acceptFriendRequest($friendOfFriend);
            }
        }

        $this->assertEquals(3, $this->sender->getMutualFriendsCount($recipients[0]));
        $this->assertEquals(3, $recipients[0]->getMutualFriendsCount($this->sender));

        $this->assertEquals(2, $this->sender->getMutualFriendsCount($recipients[1]));
        $this->assertEquals(2, $recipients[1]->getMutualFriendsCount($this->sender));
    }
}