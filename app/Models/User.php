<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'contact_handle',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'is_admin',
        'avatar_path',
    ];

    protected $appends = ['avatar_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path) return null;
        if (str_starts_with($this->avatar_path, 'http')) return $this->avatar_path;
        return Storage::disk('s3')->temporaryUrl($this->avatar_path, now()->addHours(24));
    }

    public function clothingItems()
    {
        return $this->hasMany(ClothingItem::class);
    }

    public function blocks()
    {
        return $this->hasManyThrough(User::class, Block::class, 'blocked_by', 'id', 'id', 'blocked_id');
    }

    public function blockedBy()
    {
        return $this->hasManyThrough(
            User::class,
            Block::class,
            'blocked_id',
            'id',
            'id',
            'blocked_by'
        );
    }

    public function block() : void
    {
        if (auth()->check()) {
            Block::create([
                'blocked_by' => auth()->id(),
                'blocked_id' => $this->id,
            ]);
        }
    }

    public function unblock() : void
    {
        Block::where('blocked_by', auth()->id())
            ->where('blocked_id', $this->id)
            ->delete();
    }

    public function isBlocked() : bool
    {
        return Block::where('blocked_by', auth()->id())
            ->where('blocked_id', $this->id)
            ->exists();
    }

    /**
     * Users this user is following.
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withTimestamps();
    }

    /**
     * Users who are following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id')
            ->withTimestamps();
    }
    /**
     * Follow a user.
     */
    public function follow(User $user)
    {
        return $this->followings()->attach($user->id);
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(User $user)
    {
        return $this->followings()->detach($user->id);
    }

    /**
     * Check if following a user.
     */
    public function isFollowing(User $user)
    {
        return $this->followings()->where('followed_id', $user->id)->exists();
    }

    /**
     * Check if being followed by a user.
     */
    public function isFollowedBy(User $user)
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function likes()
    {
        return $this->belongsToMany(ClothingItem::class, 'likes')->withTimestamps();
    }

    public function reports()
    {
        return $this->hasMany(\App\Models\Report::class, 'reported_user_id');
    }

}
