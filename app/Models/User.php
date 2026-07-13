<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Fillable(['name', 'email', 'password', 'is_admin', 'ai_credits'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    /** Atomically deduct credits, recording a ledger entry. Throws when the balance is insufficient. */
    public function spendCredits(int $amount, string $description): void
    {
        DB::transaction(function () use ($amount, $description) {
            $affected = static::whereKey($this->id)
                ->where('ai_credits', '>=', $amount)
                ->decrement('ai_credits', $amount);

            if ($affected === 0) {
                throw new RuntimeException('Insufficient AI credits.');
            }

            $this->creditTransactions()->create([
                'amount' => -$amount,
                'description' => $description,
            ]);
        });

        $this->refresh();
    }

    public function addCredits(int $amount, string $description): void
    {
        DB::transaction(function () use ($amount, $description) {
            static::whereKey($this->id)->increment('ai_credits', $amount);

            $this->creditTransactions()->create([
                'amount' => $amount,
                'description' => $description,
            ]);
        });

        $this->refresh();
    }
}
