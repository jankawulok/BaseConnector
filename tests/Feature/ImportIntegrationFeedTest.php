<?php

namespace Tests\Feature;

use App\Jobs\ImportIntegrationFeed;
use App\Models\Category;
use App\Models\Integration;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImportIntegrationFeedTest extends TestCase
{
    use RefreshDatabase;

    private Integration $integration;
    private string $xmlUrl = 'http://example.com/feed.xml';
    private string $xmlNotFound = 'http://example.com/not-found.xml';
    private string $invalidXmlUrl = 'http://example.com/invalid.xml';
    private string $minimalXmlUrl = 'http://example.com/minimal.xml';
    private string $xmlWithVariants = 'http://example.com/variants.xml';
    private string $malformedNumericXmlUrl = 'http://example.com/malformed-numeric.xml';
    private string $emptyXmlUrl = 'http://example.com/empty.xml';
    private string $conditionalXmlUrl = 'http://example.com/conditional.xml';
    protected function setUp(): void
    {
        parent::setUp();

        // Create test integration with import definition and feed URL
        $this->integration = Integration::factory()->create([
            'full_feed_url' => $this->xmlUrl,
            'light_feed_url' => null,
            'enabled' => true,
            'full_import_definition' => [
                'product_path' => 'products/p',
                'mappings' => [
                    'id' => ['path' => 'id'],
                    'sku' => ['path' => 'reference'],
                    'ean' => ['path' => 'ean13'],
                    'name' => [
                        'path' => 'language/lang/name',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'description' => [
                        'path' => 'language/lang/description',
                        'template' => '{{ value|striptags|trim }}'
                    ],
                    'quantity' => [
                        'path' => 'stock',
                        'template' => '{{ value|default(0)|round }}'
                    ],
                    'price' => [
                        'path' => 'price/price',
                        'template' => '{{ value|replace({",": "."})|float }}'
                    ],
                    'tax' => [
                        'path' => 'price/tax',
                        'template' => '{{ value|default(0)|round }}'
                    ],
                    'weight' => [
                        'path' => 'weight',
                        'template' => '{{ value|replace({",": "."})|float }}'
                    ],
                    'images' => [
                        'path' => 'images/img',
                        'template' => '{{ value|map(img => img|trim)|filter(img => img != "")|json_encode() }}'
                    ],
                    'features' => [
                        'path' => 'features/f',
                        'mappings' => [
                            'name' => ['path' => 'name'],
                            'value' => ['path' => 'value']
                        ],
                        'template' => '{{ value|json_encode() }}'
                    ],
                    'categories' => [
                        'path' => '.',
                        'template' => '
                            {% set cats = [] %}
                            {% if raw.cat is defined %}
                                {% set cats = cats|merge([{
                                    "id": raw.cat.attributes.id,
                                    "name": raw.cat.value
                                }]) %}
                            {% endif %}
                            {% if raw.catsub is defined %}
                                {% set cats = cats|merge([{
                                    "id": raw.catsub.attributes.id,
                                    "name": raw.catsub.value
                                }]) %}
                            {% endif %}
                            {{ cats|json_encode() }}'
                    ]
                ]
            ]
        ]);

        // Force save to ensure proper JSON encoding
        $this->integration->save();

        // Refresh the model to ensure we have the proper data
        $this->integration->refresh();

        // Set up HTTP mock
        Http::fake([
            $this->xmlUrl => Http::response($this->getTestXml(), 200),
            $this->xmlNotFound => Http::response('Not Found', 404)
        ]);
    }

    #[Test]
    public function it_imports_new_product_correctly()
    {
        // Arrange
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        Http::assertSent(function ($request) {
            return $request->url() == $this->xmlUrl;
        });

        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals('C-TT-2', $product->sku);
        $this->assertEquals('8435392603188', $product->ean);
        $this->assertEquals('Lorena Canals Dywan bawełniany', $product->name);
        $this->assertEquals('Test description', $product->description);
        $this->assertEquals(5, $product->quantity);
        $this->assertEquals(569.11, $product->price);
        $this->assertEquals(23, $product->tax);
        $this->assertEquals(2.8, $product->weight);

        // Check images
        // $images = json_decode($product->images, true);
        $this->assertCount(3, $product->images);
        $this->assertStringContainsString('lorenacanals.pl', $product->images[0]);

        // Check features
        $this->assertCount(1, $product->features);
        $this->assertEquals('Kolor', $product->features[0][0]);
        $this->assertEquals('Szary', $product->features[0][1]);
    }

    #[Test]
    public function it_handles_http_errors()
    {
        // Arrange - Override the default fake for this specific test
        Http::fake([
            $this->xmlNotFound => Http::response('Not Found', 404)
        ]);
        $this->integration->full_feed_url = $this->xmlNotFound;
        $job = new ImportIntegrationFeed($this->integration);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch XML feed');

        // Act
        $job->handle();
    }

    #[Test]
    public function it_handles_invalid_xml()
    {
        // Arrange
        Http::fake([
            $this->invalidXmlUrl => Http::response('Invalid XML content', 200)
        ]);

        $this->integration->full_feed_url = $this->invalidXmlUrl;


        $job = new ImportIntegrationFeed($this->integration);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to parse XML feed');

        // Act
        $job->handle();
    }

    #[Test]
    public function it_updates_existing_product_and_tracks_changes()
    {
        // Arrange
        $existingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '23',
            'price' => 500.00,
            'quantity' => 10
        ]);

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $existingProduct->refresh();

        // Check updated values
        $this->assertEquals(569.11, $existingProduct->price);
        $this->assertEquals(5, $existingProduct->quantity);

        // Check change logs
        $this->assertDatabaseHas('product_histories', [
            'product_auto_id' => $existingProduct->auto_id,
            'field_name' => 'price',
            'old_value' => 500.00,
            'new_value' => 569.11
        ]);

        $this->assertDatabaseHas('product_histories', [
            'product_auto_id' => $existingProduct->auto_id,
            'field_name' => 'quantity',
            'old_value' => 10,
            'new_value' => 5
        ]);
    }

    #[Test]
    public function it_handles_categories_correctly()
    {
        // Arrange
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        // Check categories
        $this->assertCount(2, $product->categories);

        // Check main category
        $mainCategory = Category::where('integration_id', $this->integration->id)
            ->where('id', '11')
            ->first();

        $this->assertNotNull($mainCategory);
        $this->assertEquals('Dywany bawełniane', $mainCategory->name);
        $this->assertTrue($product->categories->contains($mainCategory->auto_id));

        // Check subcategory
        $subCategory = Category::where('integration_id', $this->integration->id)
            ->where('id', '14')
            ->first();

        $this->assertNotNull($subCategory);
        $this->assertEquals('Kids', $subCategory->name);
        $this->assertTrue($product->categories->contains($subCategory->auto_id));
    }

    #[Test]
    public function it_marks_missing_products_as_out_of_stock()
    {
        // Arrange
        $missingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '999',
            'quantity' => 5,
        ]);

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $missingProduct->refresh();
        $this->assertEquals(0, $missingProduct->quantity);

        $this->assertDatabaseHas('product_histories', [
            'product_auto_id' => $missingProduct->auto_id,
            'field_name' => 'quantity',
            'old_value' => 5,
            'new_value' => 0
        ]);
    }

    #[Test]
    public function it_handles_missing_optional_fields()
    {
        // Arrange
        Http::fake([
            $this->minimalXmlUrl => Http::response($this->getMinimalXml(), 200)
        ]);
        $this->integration->full_feed_url = $this->minimalXmlUrl;
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals('C-TT-2', $product->sku);
        $this->assertEmpty($product->ean);
        $this->assertEquals(0, $product->weight);
        $this->assertEmpty($product->features);
        $this->assertEmpty($product->images);
    }

    #[Test]
    public function it_handles_malformed_numeric_values()
    {
        // Arrange
        Http::fake([
            $this->malformedNumericXmlUrl => Http::response($this->getMalformedNumericXml(), 200)
        ]);
        $this->integration->full_feed_url = $this->malformedNumericXmlUrl;
        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals(0, $product->quantity); // Invalid "N/A" becomes 0
        $this->assertEquals(569.11, $product->price); // "569,11" becomes 569.11
        $this->assertEquals(2.5, $product->weight); // "2,5" becomes 2.5
    }

    /**
     * Helper method to get and modify import definition
     */
    protected function getImportDefinition(string $type = 'full'): array
    {
        $definition = $type === 'full'
            ? $this->integration->full_import_definition
            : $this->integration->light_import_definition;

        // Return as-is if already an array
        if (is_array($definition)) {
            return $definition;
        }

        // Decode if string
        return json_decode($definition, true) ?: [];
    }

    /**
     * Helper method to save modified import definition
     */
    protected function saveImportDefinition(array $definition, string $type = 'full'): void
    {
        $field = $type === 'full' ? 'full_import_definition' : 'light_import_definition';
        $this->integration->$field = $definition; // Model will handle JSON encoding if needed
        $this->integration->save();
    }

    #[Test]
    public function it_handles_invalid_import_definition_path()
    {
        // Arrange
        $importDefinition = $this->getImportDefinition();
        $importDefinition['mappings']['name']['path'] = 'invalid/path';
        $this->saveImportDefinition($importDefinition);

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        $this->assertNull($product); // Product should not be created because name is required
    }

    #[Test]
    public function it_handles_custom_template_functions()
    {
        // Arrange
        $importDefinition = $this->getImportDefinition();
        $importDefinition['mappings']['price']['template'] =
            '{% if value > 500 %}{{ (value * 0.9)|round(2) }}{% else %}{{ value }}{% endif %}';
        $this->saveImportDefinition($importDefinition);

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();

        $this->assertEquals(512.20, $product->price); // 569.11 * 0.9 = 512.20
    }

    #[Test]
    public function it_handles_nested_array_mappings()
    {
        // Arrange
        $importDefinition = $this->getImportDefinition();
        $importDefinition['mappings']['variants'] = [
            'path' => 'variants/variant',
            'mappings' => [
                'id' => ['path' => 'id'],
                'price' => ['path' => 'price']
            ]
        ];
        $this->saveImportDefinition($importDefinition);

        $this->integration->full_feed_url = $this->xmlWithVariants;
        $this->integration->save();

        Http::fake([
            $this->xmlWithVariants => Http::response($this->getXmlWithVariants(), 200)
        ]);

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $product = Product::where('integration_id', $this->integration->id)
            ->where('id', '23')
            ->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->variants);
        $this->assertCount(2, $product->variants);
        $this->assertArrayHasKey('101', $product->variants);
        $this->assertEquals(199.99, $product->variants['101']['price']);
    }

    #[Test]
    public function it_handles_empty_xml_feed()
    {
        // Arrange
        Http::fake([
            $this->emptyXmlUrl => Http::response($this->getEmptyXml(), 200)
        ]);

        $this->integration->full_feed_url = $this->emptyXmlUrl;
        $this->integration->save();

        $job = new ImportIntegrationFeed($this->integration);

        // Act
        $job->handle();

        // Assert
        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function it_uses_light_feed_for_light_sync()
    {
        // Arrange
        $lightXmlUrl = 'http://example.com/light-feed.xml';
        $this->integration->light_feed_url = $lightXmlUrl;
        $this->integration->light_import_definition = [
            'product_path' => 'products/p',
            'mappings' => [
                'id' => ['path' => 'id'],
                'quantity' => [
                    'path' => 'stock',
                    'template' => '{{ value|default(0)|round }}'
                ],
                'price' => [
                    'path' => 'price',
                    'template' => '{{ value|replace({",": "."})|float }}'
                ]
            ]
        ];
        $this->integration->save();

        Http::fake([
            $lightXmlUrl => Http::response($this->getLightXml(), 200),
        ]);

        $existingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '23',
            'price' => 500.00,
            'quantity' => 10
        ]);

        $job = new ImportIntegrationFeed($this->integration, 'light');

        // Act
        $job->handle();

        // Assert
        Http::assertSent(function ($request) use ($lightXmlUrl) {
            return $request->url() == $lightXmlUrl;
        });

        $existingProduct->refresh();
        $this->assertEquals(569.11, $existingProduct->price);
        $this->assertEquals(5, $existingProduct->quantity);
    }

    #[Test]
    public function it_falls_back_to_full_feed_when_light_feed_not_available()
    {
        // Arrange
        $this->integration->light_import_definition = [
            'product_path' => 'products/p',
            'mappings' => [
                'id' => ['path' => 'id'],
                'quantity' => [
                    'path' => 'stock',
                    'template' => '{{ value|default(0)|round }}'
                ],
                'price' => [
                    'path' => 'price/price',
                    'template' => '{{ value|replace({",": "."})|float }}'
                ]
            ]
        ];
        $this->integration->save();

        $existingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '23',
            'price' => 500.00,
            'quantity' => 10
        ]);

        $job = new ImportIntegrationFeed($this->integration, 'light');

        // Act
        $job->handle();

        // Assert
        Http::assertSent(function ($request) {
            return $request->url() == $this->xmlUrl;
        });

        $existingProduct->refresh();
        $this->assertEquals(569.11, $existingProduct->price);
        $this->assertEquals(5, $existingProduct->quantity);
    }

    #[Test]
    public function it_updates_variant_prices_in_light_sync()
    {
        // Arrange
        $lightXmlUrl = 'http://example.com/light-variants.xml';
        $this->integration->light_feed_url = $lightXmlUrl;
        $this->integration->light_import_definition = [
            'product_path' => 'products/p',
            'mappings' => [
                'id' => ['path' => 'id'],
                'variants' => [
                    'path' => 'variants/variant',
                    'mappings' => [
                        'id' => ['path' => 'id'],
                        'price' => ['path' => 'price']
                    ]
                ]
            ]
        ];
        $this->integration->save();

        Http::fake([
            $lightXmlUrl => Http::response($this->getLightVariantsXml(), 200),
        ]);

        $existingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '23',
            'variants' => json_encode([
                '101' => ['price' => 189.99],
                '102' => ['price' => 289.99]
            ])
        ]);

        $job = new ImportIntegrationFeed($this->integration, 'light');

        // Act
        $job->handle();

        // Assert
        $existingProduct->refresh();
        $variants = json_decode($existingProduct->variants, true);
        $this->assertEquals(199.99, $variants['101']['price']);
        $this->assertEquals(299.99, $variants['102']['price']);

        // Check change logs
        $this->assertDatabaseHas('product_histories', [
            'product_auto_id' => $existingProduct->auto_id,
            'field_name' => 'variant_price',
            'variant_id' => '101',
            'old_value' => 189.99,
            'new_value' => 199.99
        ]);
    }

    #[Test]
    public function it_ignores_other_fields_in_light_sync()
    {
        // Arrange
        $lightXmlUrl = 'http://example.com/light-feed.xml';
        $this->integration->light_feed_url = $lightXmlUrl;
        $this->integration->light_import_definition = [
            'product_path' => 'products/p',
            'mappings' => [
                'id' => ['path' => 'id'],
                'quantity' => ['path' => 'stock'],
                'price' => ['path' => 'price']
            ]
        ];
        $this->integration->save();

        Http::fake([
            $lightXmlUrl => Http::response($this->getLightXml(), 200),
        ]);

        $existingProduct = Product::factory()->create([
            'integration_id' => $this->integration->id,
            'id' => '23',
            'price' => 500.00,
            'quantity' => 10,
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $job = new ImportIntegrationFeed($this->integration, 'light');

        // Act
        $job->handle();

        // Assert
        $existingProduct->refresh();
        $this->assertEquals('Original Name', $existingProduct->name);
        $this->assertEquals('Original Description', $existingProduct->description);
    }

    private function getTestXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <reference><![CDATA[C-TT-2]]></reference>
            <ean13>8435392603188</ean13>
            <language>
                <lang iso="pl">
                    <name><![CDATA[Lorena Canals Dywan bawełniany]]></name>
                    <description><![CDATA[Test description]]></description>
                </lang>
            </language>
            <stock>5</stock>
            <price>
                <tax>23</tax>
                <price>569.11</price>
            </price>
            <weight unit="kg">2.8</weight>
            <cat id="11"><![CDATA[Dywany bawełniane]]></cat>
            <catsub id="14"><![CDATA[Kids]]></catsub>
            <images>
                <img id="122">https://www.lorenacanals.pl/122/export.jpg</img>
                <img id="123">https://www.lorenacanals.pl/123/export.jpg</img>
                <img id="124">https://www.lorenacanals.pl/124/export.jpg</img>
            </images>
            <features>
                <f>
                    <name><![CDATA[Kolor]]></name>
                    <value><![CDATA[Szary]]></value>
                </f>
            </features>
        </p>
    </products>
</root>
XML;
    }

    private function getMinimalXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <reference><![CDATA[C-TT-2]]></reference>
            <language>
                <lang iso="pl">
                    <name><![CDATA[Minimal Product]]></name>
                    <description><![CDATA[Minimal description]]></description>
                </lang>
            </language>
            <price>
                <price>100.00</price>
            </price>
        </p>
    </products>
</root>
XML;
    }

    private function getMalformedNumericXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <reference><![CDATA[C-TT-2]]></reference>
            <language>
                <lang iso="pl">
                    <name><![CDATA[Test Product]]></name>
                    <description><![CDATA[Test description]]></description>
                </lang>
            </language>
            <stock>N/A</stock>
            <price>
                <price>569,11</price>
            </price>
            <weight unit="kg">2,5</weight>
        </p>
    </products>
</root>
XML;
    }

    private function getXmlWithVariants(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <reference><![CDATA[C-TT-2]]></reference>
            <language>
                <lang iso="pl">
                    <name><![CDATA[Test Product]]></name>
                </lang>
            </language>
            <variants>
                <variant>
                    <id>101</id>
                    <price>199.99</price>
                </variant>
                <variant>
                    <id>102</id>
                    <price>299.99</price>
                </variant>
            </variants>
        </p>
    </products>
</root>
XML;
    }

    private function getEmptyXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
    </products>
</root>
XML;
    }

    private function getConditionalTestXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <reference><![CDATA[C-TT-2]]></reference>
            <stock>N/A</stock>
            <price>
                <price>-50.00</price>
            </price>
        </p>
    </products>
</root>
XML;
    }

    private function getLightXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <stock>5</stock>
            <price>569.11</price>
            <name>Should Not Update</name>
            <description>Should Not Update</description>
        </p>
    </products>
</root>
XML;
    }

    private function getLightVariantsXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <products>
        <p>
            <id>23</id>
            <variants>
                <variant>
                    <id>101</id>
                    <price>199.99</price>
                </variant>
                <variant>
                    <id>102</id>
                    <price>299.99</price>
                </variant>
            </variants>
        </p>
    </products>
</root>
XML;
    }
}
