<?php

namespace Tests;

use Laravel\Sanctum\Sanctum;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function actingAs(UserContract $user, $abilities = ['*'])
    {
        Sanctum::actingAs($user, $abilities);

        return $this;
    }
}
