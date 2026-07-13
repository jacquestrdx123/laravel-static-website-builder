<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOrder extends Model
{
    public const TYPE_REGISTER = 'register';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_RENEW = 'renew';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'domain_id',
        'type',
        'domain',
        'regperiod',
        'credits',
        'price',
        'status',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domainRecord(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
}
