<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone'];

    public function locations(){
        return $this->hasMany(Location::class);
    }
}
