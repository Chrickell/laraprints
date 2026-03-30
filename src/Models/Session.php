<?php

namespace Chrickell\Laraprints\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

class Session extends Model
{
    use MassPrunable;

    protected $table = 'laraprints_sessions';

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('laraprints.database.connection') ?? $this->getConnectionName());
    }

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function prunable()
    {
        $days = config('laraprints.analytics.sessions_prune_after_days');

        if ($days === null) {
            return static::whereRaw('1 = 0');
        }

        return static::where('last_seen_at', '<', now()->subDays((int) $days));
    }
}
