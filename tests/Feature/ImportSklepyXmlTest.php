<?php

namespace Tests\Feature;

use App\Jobs\ImportIntegrationFeed;
use App\Models\Integration;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImportSklepyXmlTest extends TestCase
{
    use RefreshDatabase;

    private Integration $integration;
    private string $xmlUrl = 'http://example.com/sklepy.xml';

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = Integration::factory()->create([
            'full_feed_url' => $this->xmlUrl,
            'full_import_definition' => [
                'product_path' => 'PRODUCTS/PRODUCT',
                'mappings' => [
                    'id' => [
                        'path' => 'ID',
                        'template' => '{{ value|trim }}'
                    ],
                    'name' => [
                        'path' => 'NAME',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'sku' => [
                        'path' => 'SKU',
                        'template' => '{{ value|trim }}'
                    ],
                    'ean' => [
                        'path' => 'EAN',
                        'template' => '{{ value|trim }}'
                    ],
                    'description' => [
                        'path' => 'DESCRIPTION',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'price' => [
                        'path' => 'PRICE',
                        'template' => '{{ value|replace({",": "."})|float }}'
                    ],
                    'tax' => [
                        'path' => 'VAT',
                        'template' => '{{ value|default(0)|round }}'
                    ],
                    'quantity' => [
                        'path' => 'STOCK',
                        'template' => '{{ value|default(0)|round }}'
                    ],
                    'images' => [
                        'path' => 'IMGS/URL_IMG',
                        'template' => '{{ value|map(img => img|trim)|filter(img => img != "")|json_encode() }}'
                    ],
                    'url' => [
                        'path' => 'URL_PRODUCTS',
                        'template' => '{{ value|trim }}'
                    ],
                    'man_name' => [
                        'path' => 'FIRM',
                        'template' => '{{ value|trim }}'
                    ],
                    'features' => [
                        'path' => '.',
                        'template' => '{{ [
                            [ "Wiek", raw.ExtPrzedzialWiek.value],
                            ["Płeć", raw.ExtPlec.value],
                            ["Kolor", raw.ExtKolor.value],
                            ["Materiał", raw.ExtSkladSurowiec.value]
                        ]|filter(f => f[1] != "")|json_encode() }}'
                    ]
                ]
            ]
        ]);

        Http::fake([
            $this->xmlUrl => Http::response($this->getSklepyXml(), 200)
        ]);
    }

    #[Test]
    public function it_imports_sklepy_product_correctly()
    {
        // Arrange
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '20266')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals('3SBSWA', $product->sku);
        $this->assertEquals('713757022835', $product->ean);
        $this->assertEquals('3 Sprouts Organizer Kąpielowy Mors', $product->name);
        $this->assertEquals(79.33, $product->price);
        $this->assertEquals(23, $product->tax);
        $this->assertEquals(4, $product->quantity);
        $this->assertEquals('3 Sprouts', $product->man_name);
        $this->assertEquals('https://www.blueshop.pl/3-sprouts-organizer-kapielowy-mors-id-20266.html', $product->url);

        // Check images
        $this->assertCount(2, $product->images);
        $this->assertStringContainsString('blueshop.pl/dane/full/', $product->images[0]);

        // Check features
        $this->assertCount(4, $product->features);
        $this->assertEquals([
            ['Wiek', 'Od 0 lat'],
            ['Płeć', 'chłopiec'],
            ['Kolor', 'niebieski'],
            ['Materiał', 'syntetyczny kauczuk']
        ], $product->features);

        // Verify description contains cleaned content
        $this->assertStringContainsString('Ogranizer kąpielowy 3 Sprouts', $product->description);
        $this->assertStringNotContainsString('<div>', $product->description);
    }

    private function getSklepyXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<PRODUCTS>
  <PRODUCT>
    <ID><![CDATA[20266]]></ID>
    <EAN><![CDATA[ 713757022835]]></EAN>
    <SKU><![CDATA[3SBSWA]]></SKU>
    <NAME><![CDATA[3 Sprouts Organizer Kąpielowy Mors]]></NAME>
    <PRICE><![CDATA[79,330]]></PRICE>
    <MARKET_PRICE><![CDATA[119.00]]></MARKET_PRICE>
    <VAT><![CDATA[23]]></VAT>
    <STOCK><![CDATA[4]]></STOCK>
    <DESCRIPTION><![CDATA[<div>Ogranizer kąpielowy 3 Sprouts idealnie pomieści wszystkie kąpielowe skarby naszego maluszka i utrzyma je suche.</div>]]></DESCRIPTION>
    <IMGS>
      <URL_IMG sort="1"><![CDATA[https://www.blueshop.pl/dane/full/b07a559405f54718adb149ab06465949.jpg]]></URL_IMG>
      <URL_IMG sort="2"><![CDATA[https://www.blueshop.pl/dane/full/1e0aef620a67404497756ec710337fbd.jpg]]></URL_IMG>
    </IMGS>
    <URL_PRODUCTS><![CDATA[https://www.blueshop.pl/3-sprouts-organizer-kapielowy-mors-id-20266.html]]></URL_PRODUCTS>
    <FIRM role="producent"><![CDATA[3 Sprouts]]></FIRM>
    <ExtPrzedzialWiek><![CDATA[Od 0 lat]]></ExtPrzedzialWiek>
    <ExtPlec><![CDATA[chłopiec]]></ExtPlec>
    <ExtKolor><![CDATA[niebieski]]></ExtKolor>
    <ExtSkladSurowiec><![CDATA[syntetyczny kauczuk]]></ExtSkladSurowiec>
  </PRODUCT>
</PRODUCTS>
XML;
    }
}
