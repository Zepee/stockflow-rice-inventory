<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transaction_type', 'sequence_date', 'last_number'])]
class TransactionNumberSequence extends Model
{
    public $timestamps = false;
}
