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
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all trades where user owns either the buy or sell order
     * Note: This is not a standard Eloquent relationship since trades
     * are associated via buy_order_id and sell_order_id
     */
    public function getTradesCountAttribute()
    {
        return Trade::whereHas('buyOrder', function($query) {
            $query->where('user_id', $this->id);
        })->orWhereHas('sellOrder', function($query) {
            $query->where('user_id', $this->id);
        })->count();
    }

    /**
     * Get trades query where user owns either the buy or sell order
     */
    public function trades()
    {
        return Trade::whereHas('buyOrder', function($query) {
            $query->where('user_id', $this->id);
        })->orWhereHas('sellOrder', function($query) {
            $query->where('user_id', $this->id);
        });
    }
}
