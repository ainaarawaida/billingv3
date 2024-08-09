<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model implements HasAvatar
{
    use HasFactory;

    protected $guarded = ['id'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {

        // dd((boolean)$this->photo);
        if(isset($this->photo)){
            return url("storage/".$this->photo);
        }else{
            return $this->avatar_url;
        }
       
    }

    public function customers(){
        return $this->hasMany(Customer::class, 'customer_id');
    }

}
