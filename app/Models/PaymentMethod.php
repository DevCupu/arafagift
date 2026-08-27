<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'note'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * @return array{id: string, name: string, note: string|null}
     */
    public function toCatalog(): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'note' => $this->note,
        ];
    }
}
