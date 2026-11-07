<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class JsonErrorStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::post('/_uji/csrf', fn () => throw new TokenMismatchException);
            Route::post('/_uji/meledak', fn () => throw new \RuntimeException('meledak'));
            Route::post('/_uji/validasi', fn (\Illuminate\Http\Request $r) => $r->validate(['foto' => 'required']));
        });
    }

    public function test_sesi_habis_pada_request_json_memberi_401(): void
    {
        $this->postJson(route('absensi.validate-location'), ['latitude' => 1, 'longitude' => 1])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_token_csrf_tidak_cocok_pada_request_json_memberi_419(): void
    {
        $this->postJson('/_uji/csrf')->assertStatus(419)->assertJson(['success' => false]);
    }

    public function test_validasi_pada_request_json_memberi_422_dengan_errors(): void
    {
        $this->postJson('/_uji/validasi')
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('foto');
    }

    public function test_sesi_habis_dan_validasi_tidak_dicatat_sebagai_error_log(): void
    {
        Log::spy();

        $this->postJson(route('absensi.validate-location'), []);
        $this->postJson('/_uji/validasi');

        Log::shouldNotHaveReceived('error');
    }

    public function test_exception_tak_terduga_tetap_500_dan_dicatat(): void
    {
        Log::spy();

        $this->postJson('/_uji/meledak')->assertStatus(500)->assertJson(['success' => false]);

        Log::shouldHaveReceived('error')->once();
    }
}
