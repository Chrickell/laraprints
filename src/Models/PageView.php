<?php

namespace Chrickell\Laraprints\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

class PageView extends Model
{
    use HasFactory, MassPrunable;

    protected $table = 'laraprints_page_views';

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('laraprints.database.connection') ?? $this->getConnectionName());
    }

    protected $casts = [
        'device_type'     => 'string',
        'current_params'  => 'array',
        'referrer_params' => 'array',
        'viewed_at'       => 'datetime',
    ];

    public function prunable()
    {
        $days = config('laraprints.pruning.page_views_after_days');

        if ($days === null) {
            return static::whereRaw('1 = 0');
        }

        return static::where('viewed_at', '<', now()->subDays((int) $days));
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('viewed_at', [$start, $end]);
    }

    public static function getAnalytics($startDate, $endDate)
    {
        return static::byDateRange($startDate, $endDate)
            ->selectRaw('current_path, device_type, COUNT(*) as view_count')
            ->groupBy('current_path', 'device_type')
            ->get();
    }
}
