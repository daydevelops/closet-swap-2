<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function clothingItems()
    {
        return $this->hasMany(ClothingItem::class);
    }

    public function blocks()
    {
        return $this->hasManyThrough(User::class, Block::class, 'blocked_by', 'id', 'id', 'blocked_id');
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

}
