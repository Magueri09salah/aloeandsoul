<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $consented_at
 * @property Carbon|null $unsubscribed_at
 */
class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'consented_at', 'consent_text', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
