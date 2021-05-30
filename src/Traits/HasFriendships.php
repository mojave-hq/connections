<?php

namespace MojaveHQ\Friends\Traits;

use MojaveHQ\Friends\Models\Friendship;
use MojaveHQ\Friends\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait HasFriendships
{
    /**
     * @param Model $recipient
     *
     * @return \MojaveHQ\Friends\Models\Friendship|false
     */
    public function befriend(Model $recipient)
    {
        if (! $this->canBefriend($recipient)) {
            return false;
        }

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friends()->save($friendship);
      
        // Event::fire('friendships.sent', [$this, $recipient]);

        return $friendship;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {
        $deleted = $this->findFriendship($recipient)->delete();

        // Event::fire('friendships.cancelled', [$this, $recipient]);

        return $deleted;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return $this->findFriendship($recipient)
            ->whereSender($recipient)
            ->whereStatus(Status::PENDING)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasSentFriendRequestTo(Model $recipient)
    {
        return Friendship::whereRecipient($recipient)
            ->whereSender($this)
            ->whereStatus(Status::PENDING)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)
            ->where('status', Status::ACCEPTED)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        $updated = $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::ACCEPTED,
        ]);

        // Event::fire('friendships.accepted', [$this, $recipient]);
      
        return $updated;
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        $updated = $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);

        // Event::fire('friendships.denied', [$this, $recipient]);
      
        return $updated;
    }

    /**
     * @param Model $recipient
     *
     * @return \MojaveHQ\Friends\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        if (! $this->isBlockedBy($recipient)) {
            $this->findFriendship($recipient)->delete();
        }

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);
      
        $this->friends()->save($friendship);

        // Event::fire('friendships.blocked', [$this, $recipient]);

        return $friendship;
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        $deleted = $this->findFriendship($recipient)
            ->whereSender($this)
            ->delete();

        // Event::fire('friendships.unblocked', [$this, $recipient]);
      
        return $deleted;
    }

    /**
     * @param Model $recipient
     *
     * @return \MojaveHQ\Friends\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     *
     */
    public function getAllFriendships()
    {
        return $this->findFriendships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     *
     */
    public function getPendingFriendships()
    {
        return $this->findFriendships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     *
     */
    public function getAcceptedFriendships()
    {
        return $this->findFriendships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()
            ->whereRecipient($recipient)
            ->whereStatus(Status::BLOCKED)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getFriendRequests()
    {
        return Friendship::whereRecipient($this)
            ->whereStatus(Status::PENDING)
            ->get();
    }

    /**
     * This method will not return Connection models
     * It will return the 'connections' models. ex: App\User
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0)
    {
        return $this->getOrPaginate($this->getFriendsQueryBuilder(), $perPage);
    }
    
    /**
     * This method will not return Connection models
     * It will return the 'connections' models. ex: App\User
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0)
    {
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage);
    }
    
    /**
     * Get the number of connections
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * This method will not return Connection models
     * It will return the 'connections' models. ex: App\User
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0)
    {
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage);
    }

    /**
     * Get the number of connections
     *
     *
     * @return integer
     */
    public function getFriendsCount()
    {
        return $this->findFriendships(Status::ACCEPTED)->count();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function canBefriend($recipient)
    {
        // if user has Blocked the recipient and changed his mind
        // he can send a connection request after unblocking
        if ($this->hasBlocked($recipient)) {
            $this->unblockFriend($recipient);
            return true;
        }

        // if sender has a connection with the recipient return false
        if ($friendship = $this->getFriendship($recipient)) {
            // if previous connection was Denied then let the user send fr
            if ($friendship->status != Status::DENIED) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        return Friendship::betweenModels($this, $recipient);
    }

    /**
     * @param $status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null)
    {
        $query = Friendship::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            })->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
        });

        //if $status is passed, add where clause
        if (! is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'connection' model
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);

        return $this->where('id', '!=', $this->getKey())
            ->whereIn('id', array_merge(
                $friendships->pluck('recipient_id')->all(),  // recipients
                $friendships->pluck('sender_id')->all()) // senders
            );
    }
    
    /**
     * Get the query builder of the 'connection' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other)
    {
        $user1['friendships'] = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user1['recipients'] = $user1['friendships']->pluck('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->pluck('sender_id')->all();
        
        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->pluck('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->pluck('sender_id')->all();
        
        $mutualFriendships = array_unique(
                                    array_intersect(
                                        array_merge($user1['recipients'], $user1['senders']),
                                        array_merge($user2['recipients'], $user2['senders'])
                                    )
                                );

        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])
            ->whereIn('id', $mutualFriendships);
    }

    /**
     * Get the query builder for connectionsOfConnections ('connection' model)
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);

        $friendIds = array_unique(array_merge(
            $friendships->pluck('recipient_id')->all(), // recipients
            $friendships->pluck('sender_id')->all() // senders
        ));

        $friendsOfFriends = Friendship::where('status', Status::ACCEPTED)
                            ->where(function ($query) use ($friendIds) {
                                $query->where(function ($q) use ($friendIds) {
                                    $q->whereIn('sender_id', $friendIds);
                                })->orWhere(function ($q) use ($friendIds) {
                                    $q->whereIn('recipient_id', $friendIds);
                                });
                            })
                            ->get(['sender_id', 'recipient_id']);

        return $this->whereIn('id', array_unique(
                array_merge(
                    $friendsOfFriends->pluck('sender_id')->all(), // senders
                    $friendsOfFriends->pluck('recipient_id')->all()) // recipients
                ))->whereNotIn('id', $friendIds);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }
    
    protected function getOrPaginate($builder, $perPage)
    {
        if ($perPage == 0) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
    }
}