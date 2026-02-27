<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cpf_cnpj',
        'whatsapp',
        'referral_code',
        'referred_by_user_id',
        'referral_credits',
        'portal_token',
        'portal_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'portal_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'referral_credits' => 'decimal:2',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function sacTickets(): HasMany
    {
        return $this->hasMany(SacTicket::class);
    }

    public function ticketsAtribuidos(): HasMany
    {
        return $this->hasMany(SacTicket::class, 'atendente_id');
    }

    public function sacMessages(): HasMany
    {
        return $this->hasMany(SacMessage::class);
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function ensureReferralCode(): string
    {
        if (! empty($this->referral_code)) {
            return (string) $this->referral_code;
        }

        do {
            $code = 'CPF'.Str::upper(Str::random(6));
        } while (self::query()->where('referral_code', $code)->exists());

        $this->forceFill(['referral_code' => $code])->save();

        return $code;
    }
}
