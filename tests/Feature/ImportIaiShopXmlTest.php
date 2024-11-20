<?php

namespace Tests\Feature;

use App\Jobs\ImportIntegrationFeed;
use App\Models\Integration;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImportIaiShopXmlTest extends TestCase
{
    use RefreshDatabase;

    private Integration $integration;
    private string $xmlUrl = 'http://example.com/iai-shop.xml';
    private array $logMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Capture all log messages
        Log::listen(function($message) {
            $this->logMessages[] = [
                'level' => $message->level,
                'message' => $message->message,
                'context' => $message->context
            ];
        });

        $this->integration = Integration::factory()->create([
            'full_feed_url' => $this->xmlUrl,
            'full_import_definition' => [
                'product_path' => 'products/product',
                'mappings' => [
                    'id' => [
                        'path' => '@id',
                        'template' => '{{ value|trim }}'
                    ],
                    'sku' => [
                        'path' => 'sizes/size/@code_producer',
                        'template' => '{{ value|trim }}'
                    ],
                    'ean' => [
                        'path' => '@code_on_card',
                        'template' => '{{ value|trim }}'
                    ],
                    'name' => [
                        'path' => 'description/name[@xml:lang="pol"]',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'description' => [
                        'path' => 'description/long_desc[@xml:lang="pol"]',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'quantity' => [
                        'path' => 'sizes/size/stock/@available_stock_quantity',
                        'template' => '{{ value|default(0)|round }}'
                    ],
                    'price' => [
                        'path' => 'sizes/size/srp/@gross',
                        'template' => '{{ value|replace({",": "."})|float }}'
                    ],
                    'tax' => [
                        'path' => '@vat',
                        'template' => '{{ value|replace({".0": ""})|round }}'
                    ],
                    'url' => [
                        'path' => 'card/@url',
                        'template' => '{{ value|trim }}'
                    ],
                    'man_name' => [
                        'path' => 'producer/@name',
                        'template' => '{{ value|trim }}'
                    ],
                    'images' => [
                        'path' => 'images/large/image/@url',
                        'template' => '{{ value|map(img => img|trim)|filter(img => img != "")|json_encode() }}'
                    ],
                    'categories' => [
                        'path' => 'category',
                        'mappings' => [
                            'id' => ['path' => '@id'],
                            'name' => ['path' => '@name']
                        ]
                    ],
                    'features' => [
                        'path' => 'parameters/parameter',
                        'mappings' => [
                            'name' => ['path' => '@name'],
                            'value' => ['path' => 'value/@name']
                        ]
                    ]
                ]
            ]
        ]);

        Http::fake([
            $this->xmlUrl => Http::response($this->getIaiShopXml(), 200)
        ]);
    }

    protected function tearDown(): void
    {
        // Print all captured log messages
        if (!empty($this->logMessages)) {
            echo "\nLog Messages:\n";
            foreach ($this->logMessages as $log) {
                echo sprintf(
                    "\n[%s] %s %s",
                    strtoupper($log['level']),
                    $log['message'],
                    !empty($log['context']) ? json_encode($log['context'], JSON_PRETTY_PRINT) : ''
                );
            }
            echo "\n";
        }

        parent::tearDown();
    }

    #[Test]
    public function it_imports_iai_shop_product_correctly()
    {
        // Arrange
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '13')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals('5705548024888', $product->sku);
        $this->assertEquals('5705548024888', $product->ean);
        $this->assertEquals('Baby Dan - Bramka ochronna Flexi Fit, buk', $product->name);
        $this->assertEquals(299, $product->price);
        $this->assertEquals(23, $product->tax);
        $this->assertEquals(97, $product->quantity);
        $this->assertEquals('Baby Dan', $product->man_name);
        $this->assertEquals('https://hurt.scandinavianbaby.pl/product-pol-13-Baby-Dan-Bramka-ochronna-Flexi-Fit-buk.html', $product->url);
        // Check categories

        $this->assertCount(1, $product->categories);
        $this->assertEquals(1214553885, $product->categories[0]->id);
        $this->assertEquals('Baby Dan/Bramki ochronne Baby Dan', $product->categories[0]->name);
        // Check images
        $this->assertCount(8, $product->images);
        $this->assertStringContainsString('hurt.scandinavianbaby.pl/hpeciai/', $product->images[0]);

        // Check features
        $this->assertCount(9, $product->features);
        $this->assertEquals([
            ['0' => 'Wysokość towaru w centymetrach', '1' => '70'],
            ['0' => 'Długość towaru w centymetrach', '1' => '55'],
            ['0' => 'Szerokość towaru w centymetrach', '1' => '5'],
            ['0' => 'Video', '1' => 'https://www.youtube.com/watch?v=gIzXYIMBesk'],
            ['0' => 'Agito', '1' => 'tak'],
            ['0' => 'Sposób montażu', '1' => 'Przykręcana do ściany'],
            ['0' => 'Kolor', '1' => 'Brązowy'],
            ['0' => 'Towar', '1' => 'Zapytaj'],
            ['0' => 'Waga gabarytowa w gramach', '1' => '3208.33']
        ], $product->features);

        // Verify description contains cleaned content
        $this->assertStringContainsString('Poznaj bramkę ochronną Baby Dan', $product->description);
        $this->assertStringNotContainsString('<h2>', $product->description);
    }

    private function getIaiShopXml(): string
    {
        return file_get_contents(__DIR__ . '/../../storage/tests/iai-shop.xml');
    }
}
