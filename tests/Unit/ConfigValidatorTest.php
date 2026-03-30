<?php

namespace Chrickell\Laraprints\Tests\Unit;

use Chrickell\Laraprints\Support\ConfigValidator;
use Chrickell\Laraprints\Tests\TestCase;
use InvalidArgumentException;

class ConfigValidatorTest extends TestCase
{
    public function test_invalid_geo_driver_throws_clear_exception(): void
    {
        config()->set('laraprints.analytics.geo_driver', 'bad-driver');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid analytics.geo_driver value');

        ConfigValidator::validate();
    }

    public function test_invalid_ua_parser_throws_clear_exception(): void
    {
        config()->set('laraprints.analytics.ua_parser', 'bad-parser');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid analytics.ua_parser value');

        ConfigValidator::validate();
    }

    public function test_missing_maxmind_database_path_throws_exception(): void
    {
        config()->set('laraprints.analytics.geo_driver', 'maxmind');
        config()->set('laraprints.analytics.maxmind_db_path', '/tmp/laraprints-missing.mmdb');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MaxMind database not found');

        ConfigValidator::validate();
    }

    public function test_invalid_pruning_value_throws_exception(): void
    {
        config()->set('laraprints.pruning.page_views_after_days', 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a positive integer or null');

        ConfigValidator::validate();
    }
}
