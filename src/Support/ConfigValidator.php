<?php

namespace Chrickell\Laraprints\Support;

use InvalidArgumentException;

class ConfigValidator
{
    public static function validate(): void
    {
        static::validateGeoDriver();
        static::validateUaParser();
        static::validateMaxMindPath();
        static::validatePruningDays();
    }

    protected static function validateGeoDriver(): void
    {
        $driver = config('laraprints.analytics.geo_driver');

        if ($driver === null) {
            return;
        }

        if (! in_array($driver, ['stevebauman', 'maxmind'], true)) {
            throw new InvalidArgumentException(
                "[Laraprints] Invalid analytics.geo_driver value \"{$driver}\". Supported drivers: stevebauman, maxmind."
            );
        }
    }

    protected static function validateUaParser(): void
    {
        $parser = config('laraprints.analytics.ua_parser');

        if ($parser === null) {
            return;
        }

        if (! in_array($parser, ['jenssegers', 'hisorange'], true)) {
            throw new InvalidArgumentException(
                "[Laraprints] Invalid analytics.ua_parser value \"{$parser}\". Supported parsers: jenssegers, hisorange."
            );
        }
    }

    protected static function validateMaxMindPath(): void
    {
        if (config('laraprints.analytics.geo_driver') !== 'maxmind') {
            return;
        }

        $path = config('laraprints.analytics.maxmind_db_path');

        if ($path && ! file_exists($path)) {
            throw new InvalidArgumentException(
                "[Laraprints] MaxMind database not found at \"{$path}\". Download GeoLite2-Country.mmdb and update analytics.maxmind_db_path."
            );
        }
    }

    protected static function validatePruningDays(): void
    {
        $keys = [
            'laraprints.pruning.page_views_after_days',
            'laraprints.pruning.clicks_after_days',
            'laraprints.events.prune_after_days',
            'laraprints.analytics.sessions_prune_after_days',
            'laraprints.analytics.daily_stats_prune_after_days',
        ];

        foreach ($keys as $key) {
            $value = config($key);

            if ($value === null) {
                continue;
            }

            if (! is_int($value) || $value < 1) {
                $short = str_replace('laraprints.', '', $key);
                throw new InvalidArgumentException(
                    "[Laraprints] Config \"{$short}\" must be a positive integer or null, got: " . json_encode($value)
                );
            }
        }
    }
}
