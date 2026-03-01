<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'head_of_department_id',
    ];

    // Relationship: Department has many Users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Relationship: The head of the department (User)
    public function head()
    {
        return $this->belongsTo(User::class, 'head_of_department_id');
    }
}
