<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'stubs_balance',
        'is_admin',
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
            'stubs_balance' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's program progress records.
     */
    public function programs()
    {
        return $this->hasMany(UserProgram::class);
    }

    /**
     * Get the user's player stats.
     */
    public function playerStats()
    {
        return $this->hasMany(UserPlayerStats::class);
    }

    /**
     * Get the collection rewards claimed by this user.
     */
    public function collectionRewards()
    {
        return $this->hasMany(UserCollectionReward::class);
    }

    /**
     * Add stubs to the user's balance.
     */
    public function addStubs(int $amount): void
    {
        $this->increment('stubs_balance', $amount);
    }

    /**
     * Deduct stubs from the user's balance.
     */
    public function deductStubs(int $amount): bool
    {
        if ($this->stubs_balance < $amount) {
            return false;
        }
        $this->decrement('stubs_balance', $amount);
        return true;
    }
}
