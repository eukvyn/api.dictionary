<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'word_id'];

    /**
     * Relação com o modelo User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relação com o modelo Word.
     */
    public function word()
    {
        return $this->belongsTo(Word::class);
    }
}
