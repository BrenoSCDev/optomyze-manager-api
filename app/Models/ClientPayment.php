<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'value',
        'payment_date',
        'transaction_file',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
