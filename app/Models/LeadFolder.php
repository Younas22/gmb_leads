<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFolder extends Model
{
    protected $fillable = ['user_id', 'name', 'color'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->belongsToMany(SavedLead::class, 'lead_folder_items', 'folder_id', 'lead_id')
                    ->withPivot('created_at');
    }
}
