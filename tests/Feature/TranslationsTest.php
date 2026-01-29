<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
use App\Models\Translation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_translations_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/translations');

        $response->assertStatus(401);
    }

    public function test_it_returns_paginated_translations()
    {
        $this->authenticate();

        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        Translation::factory()->count(5)->create([
            'locale_id' => $locale->id,
        ]);

        $response = $this->getJson('/api/translations');


        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'key', 'content', 'locale'],
                ],
                'next_cursor',
                'prev_cursor',
            ]);
    }
    public function test_it_filters_by_key_prefix()
    {
        $this->authenticate();

        $locale = Locale::create([
            'code' => 'en',
            'name' => 'English',
        ]);

        Translation::factory()->create([
            'key' => 'app.login',
            'locale_id' => $locale->id,
        ]);

        Translation::factory()->create([
            'key' => 'dashboard.title',
            'locale_id' => $locale->id,
        ]);

        $response = $this->getJson('/api/translations?q=app');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['key' => 'app.login']);
    }

    public function test_it_filters_by_locale()
    {
        $this->authenticate();

        $en = Locale::create(['code' => 'en', 'name' => 'English']);
        $fr = Locale::create(['code' => 'fr', 'name' => 'French']);

        Translation::factory()->create(['locale_id' => $en->id]);
        Translation::factory()->create(['locale_id' => $fr->id]);

        $response = $this->getJson('/api/translations?locale=en');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['locale' => 'en']);
    }

    public function test_it_filters_by_tags()
    {
        $this->authenticate();

        $locale = Locale::create([
            'code' => 'en',
            'name' => 'English',
        ]);
        $tag = Tag::create(['name' => 'mobile']);

        $translation = Translation::factory()->create([
            'locale_id' => $locale->id,
        ]);

        $translation->tags()->attach($tag);

        Translation::factory()->create(); // unrelated

        $response = $this->getJson('/api/translations?tags=mobile');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $translation->id]);
    }

    public function test_it_applies_combined_filters()
    {
        $this->authenticate();

        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $tag = Tag::create(['name' => 'web']);

        $t = Translation::factory()->create([
            'key' => 'app.home',
            'locale_id' => $locale->id,
        ]);

        $t->tags()->attach($tag);

        $response = $this->getJson('/api/translations?q=app&locale=en&tags=web');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_export_as_json()
    {
        $this->authenticate();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        Translation::factory()->count(3)->create();

        $response = $this->getJson('/api/translations/export?format=json');

        Log::info("json response :", ['response' => $response->json()]);
        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_export_as_csv()
    {
        $this->authenticate();
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);

        Translation::factory()->create([
            'key' => 'app.test',
            'content' => 'Hello',
        ]);

        $response = $this->get('/api/translations/export?format=csv&limit=100');

        $response->assertOk();
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename=translations.csv'
        );

        $this->assertStringContainsString(
            'app.test',
            $response->streamedContent()
        );
    }
    public function test_export_respects_filters()
    {
        $this->authenticate();

        $locale = Locale::create(['code' => 'en', 'name' => 'English']);

        Translation::factory()->create([
            'key' => 'app.keep',
            'locale_id' => $locale->id,
        ]);

        Translation::factory()->create([
            'key' => 'ignore.me',
        ]);

        $response = $this->getJson('/api/translations/export?format=json&q=app');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['key' => 'app.keep']);
    }
}
