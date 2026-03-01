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

    // Relationship: Department belongs to many Users (pivot: department_user)
    public function users()
    {
        return $this->belongsToMany(User::class, 'department_user')
                    ->select(['users.id', 'users.name', 'users.email', 'users.title', 'users.avatar']);
    }

    // Relationship: The head of the department (User)
    public function head()
    {
        return $this->belongsTo(User::class, 'head_of_department_id');
    }
}
