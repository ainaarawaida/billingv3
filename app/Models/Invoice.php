<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'attachments' => 'array', 
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function recurringInvoices(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class, 'recurring_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function notes(){
        return $this->hasMany(Note::class, 'type_id');
    }

    public function updateBalanceInvoice(){
        $totalPayment = Payment::where('team_id', $this->team_id)
        ->where('invoice_id', $this->id)
        ->where('status', 'completed')->sum('total');
        $totalRefunded = Payment::where('team_id', $this->team_id)
        ->where('invoice_id', $this->id)
        ->where('status', 'refunded')->sum('total');

        $this->balance = $this->final_amount - $totalPayment + $totalRefunded; 
        if($this->balance == 0){
            $this->invoice_status = 'done'; 
        }elseif($this->invoice_status == 'done'){
            $this->invoice_status = 'new' ;
        }
        $this->update();
   
    }

    public function getTotalPayment()
    {
        $totalPayment = Payment::where('team_id', $this->team_id)
        ->where('invoice_id', $this->id)
        ->where('status', 'completed')->sum('total');
        $totalRefunded = Payment::where('team_id', $this->team_id)
        ->where('invoice_id', $this->id)
        ->where('status', 'refunded')->sum('total');
        
        return $totalPayment - $totalRefunded;
    }

}
