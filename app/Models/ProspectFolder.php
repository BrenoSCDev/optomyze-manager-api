<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectFolder extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
