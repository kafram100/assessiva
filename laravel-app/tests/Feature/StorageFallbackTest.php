<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorageFallbackTest extends TestCase
{
    public function test_storage_route_rejects_paths_outside_public_storage(): void
    {
        $this->get('/storage/../../../artisan')->assertForbidden();
    }
}
