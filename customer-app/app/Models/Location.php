<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['address', 'city', 'state', 'zip', 'customer_id'];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }
}