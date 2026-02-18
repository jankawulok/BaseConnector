<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use XMLReader;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Illuminate\Support\Facades\Storage;

class ImportIntegrationFeed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Integration $integration;
    // example import definition
// {
//     "product_path": "product",
//     "mappings": {
//         "id": {
//             "path": "id"
//         },
//         "sku": {
//             "path": "sku"
//         },
//         "ean": {
//             "path": "ean"
//         },
//         "name": {
//             "path": "name",
//             "template": "{{ value|trim }}"
//         },
//         "quantity": {
//             "path": "stock",
//             "template": "{{ value|default(0)|round }}"
//         },
//         "price": {
//             "path": "price",
//             "template": "{{ value|replace({',': '.'})|float }}"
//         },
//         "currency": {
//             "path": "currency",
//             "template": "{{ value|upper|default('USD') }}"
//         },
//         "tax": {
//             "path": "vat",
//             "template": "{{ value|default(0)|round }}"
//         },
//         "weight": {
//             "path": "weight",
//             "template": "{{ value|replace({',': '.'})|float }}"
//         },
//         "height": {
//             "path": "height",
//             "template": "{{ value|replace({',': '.'})|float }}"
//         },
//         "length": {
//             "path": "length",
//             "template": "{{ value|replace({',': '.'})|float }}"
//         },
//         "width": {
//             "path": "width",
//             "template": "{{ value|replace({',': '.'})|float }}"
//         },
//         "description": {
//             "path": "description",
//             "template": "{{ value|striptags|trim }}"
//         },
//         "description_extra1": {
//             "path": "description_extra1",
//             "template": "{{ value|striptags|trim }}"
//         },
//         "description_extra2": {
//             "path": "description_extra2",
//             "template": "{{ value|striptags|trim }}"
//         },
//         "description_extra3": {
//             "path": "description_extra3",
//             "template": "{{ value|striptags|trim }}"
//         },
//         "description_extra4": {
//             "path": "description_extra4",
//             "template": "{{ value|striptags|trim }}"
//         },
//         "man_name": {
//             "path": "manufacturer",
//             "template": "{{ value|trim }}"
//         },
//         "location": {
//             "path": "location",
//             "template": "{{ value|trim }}"
//         },
//         "url": {
//             "path": "url",
//             "template": "{{ value|trim }}"
//         },
//         "images": {
//             "path": "images/image",
//             "template": "{{ (value|default(''))|split(',')|map(image => image|trim)|filter(image => image != '')|json_encode() }}"
//         },
//         "features": {
//             "path": "features",
//             "template": "{{ (value|default(''))|split(',')|map(f => f|split(':'))|filter(f => f|length >= 2)|map(f => {(f[0]|trim): f[1]|trim})|json_encode() }}"
//         },
//         "delivery_time": {
//             "path": "delivery_time",
//             "template": "{{ value|default(1)|round }}"
//         },
//         "categories": {
//             "path": "categories/category",
//             "mappings": {
//                 "id": {
//                     "path": "id"
//                 },
//                 "name": {
//                     "path": "name"
//                 }
//             }
//         },
//         "variants": {
//             "path": "variants/variant",
//             "mappings": {
//                 "full_name": {
//                     "path": "full_name",
//                     "template": "{{ value|default(raw.name ~ ' ' ~ name)|trim }}"
//                 },
//                 "name": {
//                     "path": "name",
//                     "template": "{{ value|default('')|trim }}"
//                 },
//                 "price": {
//                     "path": "price",
//                     "template": "{{ value|default(raw.price)|replace({',': '.'})|float }}"
//                 },
//                 "quantity": {
//                     "path": "quantity",
//                     "template": "{{ value|default(0)|round }}"
//                 },
//                 "sku": {
//                     "path": "sku",
//                     "template": "{{ value|default(raw.sku ~ '-' ~ loop.index)|trim }}"
//                 },
//                 "ean": {
//                     "path": "ean",
//                     "template": "{{ value|default('')|trim }}"
//                 },
//                 "features": {
//                     "path": "features",
//                     "template": "{{ value|split(',')|map(f => f|split(':'))|filter(f => f|length >= 2)|map(f => {(f[0]|trim): f[1]|trim})|json_encode() }}"
//                 },
//                 "images": {
//                     "path": "images",
//                     "template": "{{ value|split(',')|map(img => img|trim)|filter(img => img != '')|json_encode() }}"
//                 },
//                 "weight": {
//                     "path": "weight",
//                     "template": "{{ value|default(raw.weight)|replace({',': '.'})|float }}"
//                 },
//                 "height": {
//                     "path": "height",
//                     "template": "{{ value|default(raw.height)|replace({',': '.'})|float }}"
//                 },
//                 "length": {
//                     "path": "length",
//                     "template": "{{ value|default(raw.length)|replace({',': '.'})|float }}"
//                 },
//                 "width": {
//                     "path": "width",
//                     "template": "{{ value|default(raw.width)|replace({',': '.'})|float }}"
//                 },
//                 "tax": {
//                     "path": "tax",
//                     "template": "{{ value|default(raw.tax)|round }}"
//                 }
//             }
//         }
//     }
// }

    protected array $importDefinition;
    protected array $processedIds = [];
    protected array $xmlProducts = [];
    protected string $type;
    protected string $xmlPath;
    protected string $feedUrl;

    /**
     * Create a new job instance.
     *
     * @param Integration $integration
     * @param string $type 'full' or 'light'
     * @throws \InvalidArgumentException
     */
    public function __construct(Integration $integration, $type = 'full')
    {
        $this->integration = $integration;
        $this->type = $type;

        // Get the appropriate import definition
        $rawDefinition = $type === 'full'
            ? $this->integration->full_import_definition
            : $this->integration->light_import_definition;

        // For light sync, fall back to full import definition if light is empty
        if ($type === 'light' && empty($rawDefinition)) {
            $rawDefinition = $this->integration->full_import_definition;
        }

        // Ensure we have a valid array
        if (empty($rawDefinition)) {
            throw new \InvalidArgumentException('Import definition cannot be empty');
        }

        // Convert to array if it's a JSON string
        if (is_string($rawDefinition)) {
            $decoded = json_decode($rawDefinition, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON in import definition');
            }
            $rawDefinition = $decoded;
        }

        // Final type check
        if (!is_array($rawDefinition)) {
            throw new \InvalidArgumentException('Import definition must be an array or valid JSON string');
        }

        $this->importDefinition = $rawDefinition;

        // Set feed URL with fallback for light sync
        $this->feedUrl = $type === 'full'
            ? $this->integration->full_feed_url
            : ($this->integration->light_feed_url ?? $this->integration->full_feed_url);

        Log::info('ImportIntegrationFeed initialized', [
            'integration_name' => $this->integration->name,
            'type' => $this->type,
            'feed_url' => $this->feedUrl
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('ImportIntegrationFeed starting', [
            'integration_id' => $this->integration->id,
            'type' => $this->type
        ]);

        $tempFilePath = $this->downloadFeedToTempFile($this->feedUrl);

        $reader = new XMLReader();
        $reader->open($tempFilePath);


        try {
            $pathParts = explode('/', $this->importDefinition['product_path']);
            $currentPath = '';

            // Move to each part of the path
            foreach ($pathParts as $part) {

                while ($reader->read()) {

                    if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === $part) {
                        $currentPath .= '/' . $part;
                        if ($currentPath === '/' . $this->importDefinition['product_path']) {
                            break 2; // Found the full path
                        }
                        break;
                    }
                }
            }
            do {
                // Split path into parts

                if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === end($pathParts)) {
                    // Get product XML as string and convert to array
                    $productXml = $reader->readOuterXml();
                    $productData = $this->type === 'full'
                        ? $this->extractProductData($productXml)
                        : $this->extractProductDataLight($productXml);

                    if (!empty($productData['id'])) {
                        $this->type === 'full'
                            ? $this->upsertProduct($productData)
                            : $this->updateStockAndPrice($productData);

                        $this->processedIds[] = $productData['id'];

                    }
                }
            } while ($reader->next(end($pathParts)));

            $reader->close();

            $this->handleMissingProducts();

            Log::channel('database')->info('Feed import completed successfully', [
                'integration_id' => $this->integration->id,
                'products_processed' => count($this->processedIds),
                'sync_type' => $this->type
            ]);
            // Update last sync timestamp
            $timestampField = $this->type === 'full' ? 'last_full_sync' : 'last_light_sync';
            $this->integration->update([$timestampField => now()]);

            Log::channel('database')->info('Feed import completed successfully', [
                'integration_id' => $this->integration->id,
                'products_processed' => count($this->processedIds),
                'sync_type' => $this->type
            ]);
        } catch (\Throwable $e) {
            Log::channel('database')->error('Feed import failed', [
                'integration_id' => $this->integration->id,
                'error' => $e->getMessage()
            ]);
            if ($e instanceof \ErrorException) {
                throw new \Exception('Failed to parse XML feed');
            }
            throw $e;
        }
    }

    /**
     * Update only stock and price information
     */
    private function updateStockAndPrice(array $productData)
    {
        try {
            $product = Product::where([
                'integration_id' => $this->integration->id,
                'id' => $productData['id']
            ])->first();

            if (!$product) {
                Log::warning('Product not found for light sync', [
                    'integration_id' => $this->integration->id,
                    'product_id' => $productData['id']
                ]);
                return;
            }

            // Update main product price and quantity
            if (isset($productData['quantity']) && $product->quantity != $productData['quantity']) {
                $this->trackFieldChange(
                    $product,
                    'quantity',
                    $product->quantity,
                    $productData['quantity']
                );
                $product->quantity = $productData['quantity'];
            }

            if (isset($productData['price']) && $product->price != $productData['price']) {
                $this->trackFieldChange(
                    $product,
                    'price',
                    $product->price,
                    $productData['price']
                );
                $product->price = $productData['price'];
            }

            // Update variants if present
            if (!empty($productData['variants'])) {
                $currentVariants = json_decode($product->variants ?? '[]', true);

                foreach ($productData['variants'] as $variantId => $variantData) {
                    if (
                        isset($currentVariants[$variantId]) &&
                        isset($variantData['price']) &&
                        $currentVariants[$variantId]['price'] != $variantData['price']
                    ) {

                        // Track variant price changes
                        $this->trackFieldChange(
                            $product,
                            'variant_price',
                            $currentVariants[$variantId]['price'],
                            $variantData['price'],
                            $variantId
                        );

                        // Update the variant price
                        $currentVariants[$variantId]['price'] = $variantData['price'];
                    }
                }

                $product->variants = json_encode($currentVariants);
            }

            $product->save();

        } catch (\Exception $e) {
            Log::error('Failed to update product stock and price: ' . $e->getMessage(), [
                'product_id' => $productData['id'] ?? 'unknown',
                'integration_id' => $this->integration->id
            ]);
            throw $e;
        }
    }

    /**
     * Track field changes with optional variant ID
     */
    private function trackFieldChange(Product $product, string $field, $oldValue, $newValue, ?string $variantId = null)
    {
        if ($oldValue == $newValue) {
            return;
        }

        $history = new ProductHistory([
            'product_auto_id' => $product->auto_id,
            'field_name' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'variant_id' => $variantId
        ]);

        $history->save();
    }

    /**
     * Download the feed to a temporary file.
     *
     * @param string $feedUrl
     * @return string The path to the temporary file or null on failure.
     */
    private function downloadFeedToTempFile($feedUrl): string
    {
        try {
            $response = Http::get(url: $feedUrl);

            if ($response->successful()) {
                // Create a temporary file and store the feed content
                $tempFilePath = tempnam(sys_get_temp_dir(), 'feed_') . '.xml';
                file_put_contents($tempFilePath, $response->body());
                return $tempFilePath;
            }

            throw new \Exception('Failed to fetch XML feed');
        } catch (\Exception $e) {
            Log::error('Failed to download feed', [
                'url' => $feedUrl,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch XML feed');
        }
    }

    /**
     * Converts a SimpleXMLElement to an associative array, preserving attributes without "@" prefixes.
     * Ensures that the 'value' key is a scalar when applicable.
     *
     * @param SimpleXMLElement $xml The XML object to convert.
     * @return array The resulting associative array.
     * Array
(
    [book] => Array
        (
            [0] => Array
                (
                    [attributes] => Array
                        (
                            [category] => children
                        )

                    [title] => Array
                        (
                            [attributes] => Array
                                (
                                    [lang] => en
                                )

                            [value] => Array
                                (
                                    [0] => Harry Potter
                                )

                        )

                    [author] => Array
                        (
                            [0] => J.K. Rowling
                        )

                    [year] => Array
                        (
                            [0] => 2005
                        )

                    [price] => Array
                        (
                            [0] => 29.99
                        )

                )

            [1] => Array
                (
                    [attributes] => Array
                        (
                            [category] => web
                        )

                    [title] => Array
                        (
                            [attributes] => Array
                                (
                                    [lang] => en
                                )

                            [value] => Array
                                (
                                    [0] => Learning XML
                                )

                        )

                    [author] => Array
                        (
                            [0] => Erik T. Ray
                        )

                    [year] => Array
                        (
                            [0] => 2003
                        )

                    [price] => Array
                        (
                            [0] => 39.95
                        )

                )

        )

)
     */
    public static function simpleXmlToArray(\SimpleXMLElement $xml): array
    {
        $array = [];

        // Process attributes
        foreach ($xml->attributes() as $attrName => $attrValue) {
            $array['attributes'][$attrName] = (string) $attrValue;
        }

        // Process child elements
        foreach ($xml->children() as $childName => $child) {
            $value = self::simpleXmlToArray($child);

            if (!isset($array[$childName])) {
                $array[$childName] = [];
            }

            $array[$childName][] = $value;
        }

        // Process text content
        $text = trim((string) $xml);
        if (!empty($text)) {
            $array['value'] = $text;
        }

        // Simplify child elements arrays if they contain only one item
        foreach ($array as $key => &$value) {
            if ($key === 'attributes' || $key === 'value') {
                continue;
            }

            if (is_array($value) && count($value) === 1) {
                $array[$key] = $value[0];
            }
        }
        unset($value); // Break the reference

        return $array;
    }


    private function extractProductData(string $productXml)
    {
        try {
            $productXml = simplexml_load_string($productXml, options: LIBXML_NOCDATA);
            $rawData = $this->simpleXmlToArray($productXml);

            // Initialize Twig environment for template rendering
            $loader = new ArrayLoader(['template' => '']);
            $twig = new Environment($loader);


            // Add custom filters
            $twig->addFilter(new \Twig\TwigFilter('float', function ($value) {
                return (float) str_replace(',', '.', $value);
            }));

            $twig->addFilter(new \Twig\TwigFilter('round', function ($value, $precision = 0) {
                return round((float) str_replace(',', '.', $value), $precision);
            }));

            $twig->addFilter(new \Twig\TwigFilter('text', function ($value) {
                return (string) $value;
            }));

            $productData = [];

            // Process all mappings with access to complete XML data
            foreach ($this->importDefinition['mappings'] as $field => $config) {
                // Handle nested mappings (like variants, features, categories)
                if (isset($config['mappings'])) {
                    $nestedData = [];
                    $nodes = $productXml->xpath($config['path']) ?: [];

                    foreach ($nodes as $index => $node) {
                        $itemData = [];
                        foreach ($config['mappings'] as $subField => $subConfig) {
                            $value = $node->xpath($subConfig['path'])[0] ?? '';
                            $value = is_array($value) ? implode('|', $value) : (string) $value;
                            // Convert numeric values before template processing
                            if (in_array($field, ['price', 'quantity', 'tax', 'weight'])) {
                                $value = str_replace(',', '.', $value);
                                if (is_numeric($value)) {
                                    $value = (float) $value;
                                }
                            }
                            if (isset($subConfig['template'])) {
                                $loader->setTemplate('template', $subConfig['template']);
                                $value = $twig->render('template', [
                                    'value' => $value,
                                    'raw' => $rawData,
                                    'loop' => ['index' => $index + 1]
                                ]);
                            }

                            $itemData[$subField] = $value;
                        }
                        if ($field === 'features') {
                            if (array_key_exists('name', $itemData) && array_key_exists('value', $itemData)) {
                                $nestedData[] = [
                                    $itemData['name'],
                                    $itemData['value']
                                ];
                            } else {
                                $nestedData[] = $itemData;
                            }


                        } else if ($field === 'variants') {
                            $variantKey = $itemData['id'] ?? $index;
                            $nestedData[$variantKey] = $itemData;
                        } else {
                            $nestedData[] = $itemData;
                        }
                    }

                    $productData[$field] = $nestedData;
                } else {
                    // Handle simple fields
                    $nodes = $productXml->xpath($config['path']);
                    $value = '';

                    // Check if we have any nodes and handle appropriately
                    if ($nodes) {
                        if (count($nodes) > 1) {
                            // If multiple nodes, convert to array of strings
                            $value = array_map('strval', iterator_to_array($nodes));
                            //  if nodes are complex, convert to array of arrays
                            if (is_array($value[0])) {
                                $value = array_map(function ($item) {
                                    return $this->simpleXmlToArray($item);
                                }, $value);
                            }
                        } else {
                            // Single node, get string value
                            $value = (string) $nodes[0];
                        }
                    }

                    if (isset($config['template'])) {
                        $loader->setTemplate('template', $config['template']);
                        try {
                            $value = $twig->render('template', [
                                'value' => $value,
                                'raw' => $rawData,

                            ]);
                        } catch (\Exception $e) {
                            // If template rendering fails, use empty default values based on field type
                            $value = match ($field) {
                                'price', 'quantity', 'tax', 'weight', 'height', 'length', 'width' => '0',
                                'images', 'features' => '[]',
                                default => ''
                            };
                        }
                    }

                    $decodedValue = html_entity_decode($value);
                    $productData[$field] = json_validate($decodedValue) ? json_decode($decodedValue, true) : $decodedValue;
                }
            }

            return $productData;
        } catch (\Exception $e) {
            Log::error('Failed to extract product data: ' . $e->getMessage());
            throw new \Exception('Failed to extract product data: ' . $e->getMessage());
        }
    }

    /**
     * Extract only price and stock data for light sync
     */
    private function extractProductDataLight(string $productXml)
    {
        try {
            $productXml = simplexml_load_string($productXml, options: LIBXML_NOCDATA);
            $rawData = $this->simpleXmlToArray($productXml);

            $loader = new ArrayLoader(['template' => '']);
            $twig = new Environment($loader);

            // Add custom filters
            $twig->addFilter(new \Twig\TwigFilter('float', function ($value) {
                return (float) str_replace(',', '.', $value);
            }));

            $twig->addFilter(new \Twig\TwigFilter('round', function ($value, $precision = 0) {
                return round((float) str_replace(',', '.', $value), $precision);
            }));

            $productData = [];
            $relevantFields = ['id', 'price', 'quantity', 'variants'];

            foreach ($this->importDefinition['mappings'] as $field => $config) {
                if (!in_array($field, $relevantFields)) {
                    continue;
                }

                if (isset($config['mappings'])) {
                    // Handle variants
                    if ($field === 'variants') {
                        $nodes = $productXml->xpath($config['path']) ?: [];
                        $variants = [];

                        foreach ($nodes as $index => $node) {
                            $variantData = [];
                            foreach ($config['mappings'] as $subField => $subConfig) {
                                $value = $node->xpath($subConfig['path'])[0] ?? '';
                                $value = (string) $value;

                                if (isset($subConfig['template'])) {
                                    $loader->setTemplate('template', $subConfig['template']);
                                    $value = $twig->render('template', [
                                        'value' => $value,
                                        'raw' => $rawData,
                                        'loop' => ['index' => $index + 1]
                                    ]);
                                }

                                $variantData[$subField] = $value;
                            }

                            $variantId = $variantData['id'] ?? $index;
                            $variants[$variantId] = $variantData;
                        }

                        $productData[$field] = $variants;
                    }
                } else {
                    // Handle simple fields
                    $nodes = $productXml->xpath($config['path']);
                    $value = '';

                    if ($nodes) {
                        $value = (string) $nodes[0];
                    }

                    if (isset($config['template'])) {
                        $loader->setTemplate('template', $config['template']);
                        try {
                            $value = $twig->render('template', [
                                'value' => $value,
                                'raw' => $rawData
                            ]);
                        } catch (\Exception $e) {
                            // Use appropriate defaults for light sync
                            $value = match ($field) {
                                'price', 'quantity' => '0',
                                default => ''
                            };
                        }
                    }

                    // Convert numeric values
                    if (in_array($field, ['price', 'quantity'])) {
                        $value = str_replace(',', '.', $value);
                        $value = is_numeric($value) ? (float) $value : 0;
                    }

                    $productData[$field] = $value;
                }
            }

            return $productData;
        } catch (\Exception $e) {
            Log::error('Failed to extract light product data: ' . $e->getMessage());
            throw new \Exception('Failed to extract light product data: ' . $e->getMessage());
        }
    }

    /**
     * Insert or update the product in the database.
     *
     * @param array $productData
     */
    private function upsertProduct($productData)
    {
        try {
            // Validate required fields
            if (empty($productData['id']) || empty($productData['name'])) {
                Log::warning('Skipping product due to missing required fields', [
                    'integration_id' => $this->integration->id,
                    'product_id' => $productData['id'] ?? 'MISSING',
                    'product_name' => $productData['name'] ?? 'MISSING',
                    'sync_type' => $this->type
                ]);
                return null;
            }

            // Add ID to processed list
            $this->processedIds[] = $productData['id'];

            // Find existing product or create new one using composite key
            $product = Product::firstOrNew([
                'integration_id' => $this->integration->id,
                'id' => $productData['id']
            ]);

            // Track changes if product exists
            if ($product->exists) {
                $newPrice = $productData['price'] ?? 0;
                $newQuantity = $productData['quantity'] ?? 0;

                if ($product->price != $newPrice) {
                    $this->trackFieldChange(
                        $product,
                        'price',
                        $product->price,
                        $newPrice
                    );
                }

                if ($product->quantity != $newQuantity) {
                    $this->trackFieldChange(
                        $product,
                        'quantity',
                        $product->quantity,
                        $newQuantity
                    );
                }

                if (isset($productData['variants'])) {
                    $this->trackVariantChanges($product, $productData['variants']);
                }
            }

            // Update or set product fields with proper defaults
            $product->fill([
                'id' => $productData['id'],
                'name' => $productData['name'],
                'sku' => $productData['sku'] ?? '',
                'description' => $productData['description'] ?? '',
                'description_extra1' => $productData['description_extra1'] ?? '',
                'description_extra2' => $productData['description_extra2'] ?? '',
                'description_extra3' => $productData['description_extra3'] ?? '',
                'description_extra4' => $productData['description_extra4'] ?? '',
                'price' => $productData['price'] ?? 0,
                'quantity' => $productData['quantity'] ?? 0,
                'currency' => $productData['currency'] ?? 'PLN',
                'tax' => $productData['tax'] ?? 0,
                'weight' => $productData['weight'] ?? 0,
                'height' => $productData['height'] ?? 0,
                'length' => $productData['length'] ?? 0,
                'width' => $productData['width'] ?? 0,
                'ean' => $productData['ean'] ?? '',
                'man_name' => $productData['man_name'] ?? '',
                'location' => $productData['location'] ?? '',
                'url' => $productData['url'] ?? '',
                'images' => !empty($productData['images']) ? $productData['images'] : [],
                'features' => !empty($productData['features']) ? $productData['features'] : [],
                'delivery_time' => $productData['delivery_time'] ?? 1,
                'variants' => !empty($productData['variants']) ? $productData['variants'] : []
            ]);

            // Save the product first to ensure we have an auto_id
            $product->save();

            // Handle categories if present
            if (!empty($productData['categories'])) {
                $categoryAutoIds = [];

                foreach ($productData['categories'] as $categoryData) {
                    if (!empty($categoryData['id']) && !empty($categoryData['name'])) {
                        $category = Category::firstOrCreate(
                            [
                                'integration_id' => $this->integration->id,
                                'id' => $categoryData['id']
                            ],
                            [
                                'name' => $categoryData['name']
                            ]
                        );

                        $categoryAutoIds[] = $category->auto_id;
                    }
                }

                // Sync categories using auto_ids only if we have valid categories
                if (!empty($categoryAutoIds)) {
                    $product->categories()->sync($categoryAutoIds);
                }
            } else {
                // If no categories provided, detach all
                $product->categories()->detach();
            }

            return $product;

        } catch (\Exception $e) {

            Log::error('Failed to upsert product: ' . $e->getMessage(), [
                'id' => $productData['id'] ?? 'unknown',
                'integration_id' => $this->integration->id,
                'product_data' => $productData
            ]);
            throw $e;
        }
    }

    /**
     * Track changes in product variants
     */
    private function trackVariantChanges(Product $product, array $newVariants)
    {
        $existingVariants = json_decode($product->variants, true) ?: [];

        foreach ($newVariants as $variantId => $newVariant) {
            $existingVariant = $existingVariants[$variantId] ?? null;

            if ($existingVariant) {
                // Track price changes
                if (isset($newVariant['price']) && $newVariant['price'] != $existingVariant['price']) {
                    $this->trackFieldChange(
                        $product,
                        'price',
                        $existingVariant['price'],
                        $newVariant['price'],
                        $variantId
                    );
                }

                // Track quantity changes
                if (isset($newVariant['quantity']) && $newVariant['quantity'] != $existingVariant['quantity']) {
                    $this->trackFieldChange(
                        $product,
                        'quantity',
                        $existingVariant['quantity'],
                        $newVariant['quantity'],
                        $variantId
                    );
                }
            }
        }
    }

    /**
     * Mark products not found in the current feed as unavailable (quantity = 0).
     */
    private function handleMissingProducts()
    {
        try {
            // Find all products for this integration that weren't in the XML
            $missingProducts = Product::where('integration_id', $this->integration->id)
                ->whereNotIn('id', $this->processedIds)
                ->get();

            foreach ($missingProducts as $product) {
                // Track quantity change if it's not already 0
                if ($product->quantity != 0) {
                    $this->trackFieldChange(
                        $product,
                        'quantity',
                        $product->quantity,
                        0
                    );
                }

                // Set quantity to 0
                $product->quantity = 0;
                $product->save();

                Log::info('Product marked as out of stock (not found in feed)', [
                    'integration_id' => $this->integration->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku
                ]);
            }

            // Log summary
            if ($missingProducts->count() > 0) {
                Log::info('Missing products summary', [
                    'integration_id' => $this->integration->id,
                    'products_count' => $missingProducts->count(),
                    'processed_count' => count($this->processedIds)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle missing products: ' . $e->getMessage(), [
                'integration_id' => $this->integration->id
            ]);
            throw $e;
        }
    }

    private function processXmlAttributes($data)
    {
        if (is_array($data)) {
            // Handle @attributes specially
            if (isset($data['@attributes'])) {
                foreach ($data['@attributes'] as $key => $value) {
                    $data[$key] = $value;
                }
                unset($data['@attributes']);
            }

            // Recursively process all array elements
            foreach ($data as $key => $value) {
                $data[$key] = $this->processXmlAttributes($value);
            }
        }
        return $data;
    }
}
