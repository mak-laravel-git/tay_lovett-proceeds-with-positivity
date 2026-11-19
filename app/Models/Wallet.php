<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'pending_cents', 'available_cents', 'reserved_cents'])]
class Wallet extends Model
{
    //
}
