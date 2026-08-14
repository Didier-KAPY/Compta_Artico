<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrenchLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_validation_messages_are_in_french(): void
    {
        $validator = Validator::make([], ['email' => ['required', 'email']]);

        $this->assertSame('fr', app()->getLocale());
        $this->assertSame('Le champ adresse e-mail est obligatoire.', $validator->errors()->first('email'));
    }

    public function test_system_error_pages_are_in_french(): void
    {
        $this->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable');
    }
}
