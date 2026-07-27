<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
        ];
    }
}
