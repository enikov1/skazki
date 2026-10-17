<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogLandingRedirect extends Model
{
    protected $fillable = ['type', 'old_slug'];
}
