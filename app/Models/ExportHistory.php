<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'export_type',
        'records_count',
        'filters'
    ];

    protected $casts = [
        'filters' => 'array'
    ];

    /**
     * Get the user that owns the export history
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
