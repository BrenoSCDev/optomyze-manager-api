<?php

namespace App\Models;

use App\Http\Controllers\TaskController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_category_id',
        'client_id',
        'title',
        'description',
        'assignee_id',
        'priority',
        'due_date',
    ];

    public function category()
    {
        return $this->belongsTo(TaskCategory::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function type()
    {
        // replace 'task_category_id' with the actual FK column in your tasks table
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function docs()
    {
        return $this->hasMany(TaskDoc::class, 'task_id');
    }

}
