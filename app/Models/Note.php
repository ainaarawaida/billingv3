<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function related_note(): BelongsTo
    {
        if($this->type == 'quotation')
            return $this->belongsTo(Quotation::class, 'type_id');
        else if($this->type == 'invoice')
            return $this->belongsTo(Invoice::class, 'type_id');
        else if($this->type == 'recurring_invoice')
            return $this->belongsTo(RecurringInvoice::class, 'type_id');
        else if($this->type == 'payment')
            return $this->belongsTo(Payment::class, 'type_id');
        else{
            return $this ;
        }
    }
}
