<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_sent_to_the_indonesian_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke aplikasi');
    }
}
