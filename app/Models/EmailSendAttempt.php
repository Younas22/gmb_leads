<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSendAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];
}
