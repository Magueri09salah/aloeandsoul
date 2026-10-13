<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $read_at
 */
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'message', 'ip_hash'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
