<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'note'])]
class PaymentMethod extends Model
{
    public function toCatalog(): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'note' => $this->note,
        ];
    }
}
