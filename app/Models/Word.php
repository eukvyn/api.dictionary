<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    protected $fillable = ['word'];

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function accessedBy()
    {
        return $this->belongsToMany(User::class, 'histories')->withTimestamps();
    }
}
