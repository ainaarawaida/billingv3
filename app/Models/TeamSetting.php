<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamSetting extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'payment_gateway' => 'array', // Cast JSON data to an array
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
