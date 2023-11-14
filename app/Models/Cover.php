<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cover extends Model
{
    use HasFactory;

    protected $fillable = ['description','balance','type'];


    //relacion uno a muchos con cover_details
    public function details(){

        return $this->hasMany(CoverDetail::class);
    }
}
