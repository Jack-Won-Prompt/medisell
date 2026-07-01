<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankCollectJob extends Model
{
    protected $fillable = [
        'job_id', 'bank_code', 'account_num', 's_date', 'e_date', 'state', 'tx_count',
    ];
}
