<?php

namespace App\Models;

use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'eta', 'price'])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    /**
     * @return array{id: string, name: string, eta: string|null, price: float|int}
     */
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
