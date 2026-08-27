<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_test_db_is_isolated(): void
    {
        $this->assertSame('rahs_test', config('database.connections.'.config('database.default').'.database'));

        $admin = DB::table('auth_users')->where('email', 'admin@rahs.mv')->first();
        $this->assertNotNull($admin);
    }
}
