<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgDoc extends Model
{
    protected $table = 'org_docs';

    protected $fillable = [
        'name',
        'path',
    ];
}
