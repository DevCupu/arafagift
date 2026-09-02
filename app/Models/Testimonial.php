<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['rating', 'quote', 'name', 'city', 'context'])]
class Testimonial extends Model
{
    use HasFactory;
}
