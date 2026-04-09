<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // triggers RefreshDatabase via setUpTraits()

        if (in_array(
            \Illuminate\Foundation\Testing\RefreshDatabase::class,
            class_uses_recursive(static::class)
        )) {
            $this->seed(\Database\Seeders\RoleSeeder::class);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
