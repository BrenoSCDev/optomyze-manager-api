<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'date',
        'description',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
