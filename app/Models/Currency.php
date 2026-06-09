<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'symbol', 'decimal_places'])]
class Currency extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;
}
