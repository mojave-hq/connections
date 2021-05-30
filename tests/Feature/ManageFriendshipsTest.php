<?php

namespace MojaveHQ\Friends\Tests\Feature;

use MojaveHQ\Friends\Tests\User;
use MojaveHQ\Friends\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManageFriendshipsTest extends TestCase
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
    public function a_user_can_send_a_friend_request()
    {        
        $this->sender->befriend($this->recipient);
        
        $this->assertCount(1, $this->recipient->getFriendRequests());
    }
    
    /** @test */
    public function a_user_cannot_send_a_friend_request_when_frienship_is_pending()
    {
        $this->sender->befriend($this->recipient);
        $this->sender->befriend($this->recipient);
        $this->sender->befriend($this->recipient);
        
        $this->assertCount(1, $this->recipient->getFriendRequests());
    }
    
    /** @test */
    public function a_user_can_send_a_friend_request_after_frienship_is_denied()
    {
        $this->sender->befriend($this->recipient);
        $this->recipient->denyFriendRequest($this->sender);
        
        $this->sender->befriend($this->recipient);
        
        $this->assertCount(1, $this->recipient->getFriendRequests());
    }

    /** @test */
    public function a_user_can_cancel_a_friend_request()
    {
        $this->sender->befriend($this->recipient);
        $this->assertCount(1, $this->recipient->getFriendRequests());

        $this->sender->unfriend($this->recipient);
        $this->assertCount(0, $this->recipient->getFriendRequests());

        $this->sender->befriend($this->recipient);
        $this->assertCount(1, $this->recipient->getFriendRequests());

        $this->recipient->acceptFriendRequest($this->sender);
        $this->assertEquals(true, $this->recipient->isFriendWith($this->sender));

        $this->sender->unfriend($this->recipient);
        $this->assertEquals(false, $this->recipient->isFriendWith($this->sender));
    }

    /** @test */
    public function a_user_is_friends_with_another_user_when_the_friend_request_is_accepted()
    {
        $this->sender->befriend($this->recipient);

        $this->recipient->acceptFriendRequest($this->sender);
        
        $this->assertTrue($this->recipient->isFriendWith($this->sender));
        $this->assertTrue($this->sender->isFriendWith($this->recipient));

        $this->assertCount(0, $this->recipient->getFriendRequests());
    }
    
    /** @test */
    public function a_user_is_not_friends_with_another_user_until_the_friend_request_is_accepted()
    {
        $this->sender->befriend($this->recipient);
        
        $this->assertFalse($this->recipient->isFriendWith($this->sender));
        $this->assertFalse($this->sender->isFriendWith($this->recipient));
    }
    
    /** @test */
    public function a_user_has_a_friend_request_from_another_user_when_they_receive_a_friend_request()
    {
        $this->sender->befriend($this->recipient);
        
        $this->assertTrue($this->recipient->hasFriendRequestFrom($this->sender));
        $this->assertFalse($this->sender->hasFriendRequestFrom($this->recipient));
    }

    /** @test */
    public function a_user_has_sent_a_friend_request_to_another_user_if_the_user_has_already_sent_a_request()
    {
        $this->sender->befriend($this->recipient);

        $this->assertFalse($this->recipient->hasSentFriendRequestTo($this->sender));
        $this->assertTrue($this->sender->hasSentFriendRequestTo($this->recipient));
    }

    /** @test */
    public function a_user_has_no_friend_request_from_another_user_if_the_user_accepted_the_friend_request()
    {
        $this->sender->befriend($this->recipient);

        $this->recipient->acceptFriendRequest($this->sender);
        
        $this->assertFalse($this->recipient->hasFriendRequestFrom($this->sender));
        $this->assertFalse($this->sender->hasFriendRequestFrom($this->recipient));
    }
    
    /** @test */
    public function a_user_cannot_accept_own_friend_request()
    {
        $this->sender->befriend($this->recipient);
        
        $this->sender->acceptFriendRequest($this->recipient);
        $this->assertFalse($this->recipient->isFriendWith($this->sender));
    }
    
    /** @test */
    public function a_user_can_deny_a_friend_request()
    {
        $this->sender->befriend($this->recipient);
        
        $this->recipient->denyFriendRequest($this->sender);
        
        $this->assertFalse($this->recipient->isFriendWith($this->sender));

        $this->assertCount(0, $this->recipient->getFriendRequests());
        $this->assertCount(1, $this->sender->getDeniedFriendships());
    }
    
    /** @test */
    public function a_user_can_block_another_user()
    {
        $this->sender->blockFriend($this->recipient);
        
        $this->assertTrue($this->recipient->isBlockedBy($this->sender));
        $this->assertTrue($this->sender->hasBlocked($this->recipient));

        $this->assertFalse($this->sender->isBlockedBy($this->recipient));
        $this->assertFalse($this->recipient->hasBlocked($this->sender));
    }
    
    /** @test */
    public function a_user_can_unblock_a_blocked_user()
    {
        $this->sender->blockFriend($this->recipient);
        $this->sender->unblockFriend($this->recipient);
        
        $this->assertFalse($this->recipient->isBlockedBy($this->sender));
        $this->assertFalse($this->sender->hasBlocked($this->recipient));
    }
    
    /** @test */
    public function a_user_block_is_permanent_unless_blocker_unblocks()
    {
        $this->sender->blockFriend($this->recipient);
        $this->assertTrue($this->recipient->isBlockedBy($this->sender));

        $this->recipient->blockFriend($this->sender);
        
        $this->assertTrue($this->sender->isBlockedBy($this->recipient));
        $this->assertTrue($this->recipient->isBlockedBy($this->sender));
        
        $this->sender->unblockFriend($this->recipient);
    
        $this->assertTrue($this->sender->isBlockedBy($this->recipient));
        $this->assertFalse($this->recipient->isBlockedBy($this->sender));
    
        $this->recipient->unblockFriend($this->sender);
        $this->assertFalse($this->sender->isBlockedBy($this->recipient));
        $this->assertFalse($this->recipient->isBlockedBy($this->sender));
    }
    
    /** @test */
    public function a_user_can_send_friend_request_to_user_who_is_blocked()
    {
        $this->sender->blockFriend($this->recipient);
        $this->sender->befriend($this->recipient);
        $this->sender->befriend($this->recipient);
        
        $this->assertCount(1, $this->recipient->getFriendRequests());
    }
}