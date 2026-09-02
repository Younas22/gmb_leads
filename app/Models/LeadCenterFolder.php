<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadCenterFolder extends Model
{
    protected $fillable = ['user_id', 'name', 'color'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(LeadCenterLead::class, 'folder_id');
    }
}
