<?php

namespace Chrickell\Laraprints\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

class Click extends Model
{
    use MassPrunable;

    protected $table = 'laraprints_clicks';

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('laraprints.database.connection') ?? $this->getConnectionName());
    }

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function prunable()
    {
        $days = config('laraprints.pruning.clicks_after_days');

        if ($days === null) {
            return static::whereRaw('1 = 0');
        }

        return static::where('clicked_at', '<', now()->subDays((int) $days));
    }
}
