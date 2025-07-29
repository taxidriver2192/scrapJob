<?php

namespace Tests\Unit;

use App\Models\CityAlias;
use App\Models\ZipCode;
use App\Services\CityZipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityZipServiceTest extends TestCase
{
    use RefreshDatabase;

    private CityZipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CityZipService();

        // Set up test data
        $this->seedTestData();
    }

    /** @test */
    public function it_normalizes_city_names_correctly()
    {
        $this->assertEquals('kobenhavn', $this->service->normalize('København'));
        $this->assertEquals('aalborg', $this->service->normalize('Ålborg'));
        $this->assertEquals('arhus', $this->service->normalize('Århus'));
        $this->assertEquals('test city', $this->service->normalize('  Test   City  '));
        $this->assertEquals('test-city', $this->service->normalize('Test-City!!!'));
    }

    /** @test */
    public function it_finds_zip_codes_for_normalized_cities()
    {
        $zips = $this->service->zipsFor('København');

        $this->assertCount(2, $zips);
        $this->assertTrue($zips->pluck('postnr')->contains('1000'));
        $this->assertTrue($zips->pluck('postnr')->contains('1150'));
    }

    /** @test */
    public function it_finds_zip_codes_through_aliases()
    {
        $zips = $this->service->zipsFor('CPH');

        $this->assertCount(2, $zips);
        $this->assertTrue($zips->pluck('postnr')->contains('1000'));
        $this->assertTrue($zips->pluck('postnr')->contains('1150'));
    }

    /** @test */
    public function it_returns_best_zip_code()
    {
        // Should prefer higher weight (1150 has weight 10, 1000 has weight 5)
        $bestZip = $this->service->bestZip('København');
        $this->assertEquals('1150', $bestZip);
    }

    /** @test */
    public function it_respects_context_zip_when_valid()
    {
        // Context ZIP 1000 is valid for København, so it should be preferred
        $bestZip = $this->service->bestZip('København', '1000');
        $this->assertEquals('1000', $bestZip);
    }

    /** @test */
    public function it_ignores_invalid_context_zip()
    {
        // Context ZIP 2000 is not valid for København
        $bestZip = $this->service->bestZip('København', '2000');
        $this->assertEquals('1150', $bestZip); // Should fall back to best match
    }

    /** @test */
    public function it_returns_null_for_unknown_cities()
    {
        $zips = $this->service->zipsFor('UnknownCity');
        $this->assertTrue($zips->isEmpty());

        $bestZip = $this->service->bestZip('UnknownCity');
        $this->assertNull($bestZip);
    }

    /** @test */
    public function it_checks_if_city_is_known()
    {
        $this->assertTrue($this->service->isKnownCity('København'));
        $this->assertTrue($this->service->isKnownCity('CPH')); // Via alias
        $this->assertFalse($this->service->isKnownCity('UnknownCity'));
    }

    /** @test */
    public function it_provides_comprehensive_city_info()
    {
        $info = $this->service->getCityInfo('CPH');

        $this->assertEquals('CPH', $info['input']);
        $this->assertEquals('cph', $info['normalized']);
        $this->assertEquals('kobenhavn', $info['target_city']);
        $this->assertTrue($info['is_alias']);
        $this->assertContains('1000', $info['zip_codes']);
        $this->assertContains('1150', $info['zip_codes']);
        $this->assertEquals('1150', $info['best_zip']);
    }

    private function seedTestData(): void
    {
        // Create test ZIP codes
        ZipCode::create([
            'postnr' => '1000',
            'city' => 'København K',
            'city_norm' => 'kobenhavn',
            'lat' => 55.6761,
            'lon' => 12.5683,
            'weight' => 5
        ]);

        ZipCode::create([
            'postnr' => '1150',
            'city' => 'København K',
            'city_norm' => 'kobenhavn',
            'lat' => 55.6761,
            'lon' => 12.5683,
            'weight' => 10
        ]);

        ZipCode::create([
            'postnr' => '8000',
            'city' => 'Aarhus C',
            'city_norm' => 'aarhus',
            'lat' => 56.1629,
            'lon' => 10.2039,
            'weight' => 0
        ]);

        // Create test aliases
        CityAlias::create(['alias' => 'cph', 'city_norm' => 'kobenhavn']);
        CityAlias::create(['alias' => 'kobenhavn k', 'city_norm' => 'kobenhavn']);
    }
}
