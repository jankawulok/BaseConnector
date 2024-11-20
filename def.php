<?php

$definition = [
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
            ];


echo json_encode($definition, JSON_PRETTY_PRINT);
