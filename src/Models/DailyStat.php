<?php

namespace Chrickell\Laraprints\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

class DailyStat extends Model
{
    use MassPrunable;

    protected $table = 'laraprints_daily_stats';

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('laraprints.database.connection') ?? $this->getConnectionName());
    }

    protected $casts = [
        'date' => 'date',
    ];

    public function prunable()
    {
        $days = config('laraprints.analytics.daily_stats_prune_after_days');

        if ($days === null) {
            return static::whereRaw('1 = 0');
        }

        return static::where('date', '<', now()->subDays((int) $days)->toDateString());
    }
}
