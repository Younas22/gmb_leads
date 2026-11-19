<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A customer's own campaign template (plain text with {{variables}}). */
class UserEmailTemplate extends Model
{
    protected $guarded = ['id'];
}
