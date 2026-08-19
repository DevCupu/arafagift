<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'eta', 'price'])]
class ShippingMethod extends Model
{
    public function toCatalog(): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'eta' => $this->eta,
            'price' => $this->price,
        ];
    }
}
