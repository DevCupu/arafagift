<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['store_name', 'tagline', 'email', 'whatsapp', 'address', 'free_shipping_from', 'bulk_minimum'])]
class Setting extends Model
{
    //
}
