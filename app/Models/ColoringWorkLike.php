<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColoringWorkLike extends Model
{
    protected $fillable = ['user_id', 'coloring_submission_id'];
}
