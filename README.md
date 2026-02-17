# BaseConnector

BaseConnector is a robust Laravel application designed to synchronize product data from various external XML/JSON feeds into a unified database. It features a powerful, flexible mapping engine, background job processing, and a modern administrative interface built with Backpack for Laravel.

## 🚀 Key Features

- **Dynamic Data Mapping**: Configure how external data maps to internal fields using JSON definitions and Twig templates.
- **Background Processing**: Efficiently handles large feeds using `XMLReader` and Laravel Queues.
- **Admin Dashboard**: Comprehensive management of integrations, product history, alerts, and logs via Backpack v7.
- **Full & Light Sync**: Support for complete product imports or fast price/stock updates.
- **Dockerized**: Ready for deployment with FrankenPHP and Cloudflare Tunnel integration.

---

## 🛠 Project Setup

### Requirements
- **PHP**: 8.5+
- **Database**: MariaDB/MySQL
- **Server**: FrankenPHP (recommended) or any Laravel-compatible environment.

### Installation
1.  **Clone the repository**:
    ```bash
    git clone [repository-url]
    cd BaseConnector
    ```
2.  **Install dependencies**:
    ```bash
    composer install
    npm install && npm run build
    ```
3.  **Environment Setup**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4.  **Database Migration**:
    ```bash
    php artisan migrate
    ```

---

## 🐳 Docker Setup

This project includes a unified Docker setup that bundles the web server (FrankenPHP), background workers, scheduler, and a Cloudflare Tunnel for secure public access.

### 1. Build the Image
Run the following from the project root:
```bash
docker build -t base-connector .
```

### 2. Environment Variables
The container relies on these key variables:

| Variable | Description | Required | Example |
| :--- | :--- | :--- | :--- |
| `CLOUDFLARE_TUNNEL_TOKEN` | Your Cloudflare Tunnel connector token. | Yes | `eyJh...` |
| `APP_URL` | The public URL of your application. | Yes | `https://base.example.com` |
| `DB_CONNECTION` | Database driver (defaults to `sqlite`). | No | `sqlite` |


### 3. Launching the Container
```bash
docker run -d \
  --name base-connector \
  -e CLOUDFLARE_TUNNEL_TOKEN=your_token_here \
  -e APP_URL=https://your-custom-domain.com \
  -v base_connector_data:/app/storage \
  -v base_connector_db:/app/database \
  base-connector
```

> [!TIP]
> **Persistence**: Use named volumes (as shown above) to ensure logs, uploads, and the SQLite database persist between container updates.

### 4. Monitoring
View the combined logs of the server, workers, and tunnel:
```bash
docker logs -f base-connector
```

---

## 🗺 Data Mapping Documentation

BaseConnector uses a flexible, engine-agnostic mapping system to transform external product feeds (XML or JSON) into a standardized internal format.

### 1. Overview

The mapping definition is a JSON object (or PHP array) that instructs the `ImportIntegrationFeed` job on:
1.  **Where to find products** in the feed (`product_path`).
2.  **How to extract individual fields** using XPath-like selectors (`path`).
3.  **How to transform data** using Twig templates (`template`).

### 2. Top-Level Configuration

| Key | Description | Example |
| :--- | :--- | :--- |
| `product_path` | The relative path to the individual product node in the feed. | `"products/product"` |
| `mappings` | An object where keys are internal field names and values are field configurations. | `{ "name": { ... }, "price": { ... } }` |

### 3. Field Configuration

Each field in the `mappings` object can have the following properties:

#### `path`
An XPath-like selector relative to the product node. 
- For XML: `description/name[@xml:lang="pol"]` or `@id` (for attributes).
- For JSON: Standard object property access.

#### `template` (Optional)
A Twig template used to process the extracted value. Twig provides powerful logic, filters, and access to the entire product node.

**Available Variables in Twig:**
- `value`: The raw value extracted from the current `path`.
- `raw`: An associative array reflecting the **entire** current product node.
- `loop.index`: Available only in nested mappings, represents the 1-based index of the current item.

**Custom Twig Filters:**
- `float`: Converts a string to a float, automatically handling comma decimal separators.
- `round(precision)`: Rounds a numeric value.
- `text`: Force-casts a value to a string.

> [!TIP]
> If a template returns a valid JSON string, it is automatically decoded into an array/object before being saved. This is commonly used for `images` and `features`.

### 4. Hierarchical Mapping (Nested Data)

#### Categories
Mapped as a list of objects.
```json
"categories": {
    "path": "categories/category",
    "mappings": {
        "id": { "path": "id" },
        "name": { "path": "name" }
    }
}
```

#### Features (Attributes)
Features are stored as a list of `[name, value]` pairs.
```json
"features": {
    "path": "parameters/parameter",
    "mappings": {
        "name": { "path": "@name" },
        "value": { "path": "value" }
    }
}
```

#### Variants
Variants are extracted as an associative array. If an `id` mapping is provided within the variant, it is used as the key.
```json
"variants": {
    "path": "variants/variant",
    "mappings": {
        "sku": { "path": "sku", "template": "{{ value|default(raw.sku ~ '-' ~ loop.index) }}" },
        "price": { "path": "price", "template": "{{ value|default(raw.price)|float }}" }
    }
}
```

### 5. Behavior Notes

- **Auto-Normalization**: For numeric fields, the system automatically attempts to normalize strings (replacing `,` with `.`) before template processing.
- **Handling Missing Nodes**: If an XPath returns no nodes, the system defaults to sensible values (`0`, `[]`, or `""`).
- **Orphan Handling**: Products missing from a full feed are automatically marked with `quantity = 0`.
- **Light Sync mode**: Only updates `price`, `quantity`, and `variants` to maximize performance.

### 6. Exhaustive Example (All Fields)

This example demonstrates a mapping that utilizes every supported field for both the main product and its variants.

```json
{
    "product_path": "catalog/item",
    "mappings": {
        "id": { "path": "external_id" },
        "sku": { "path": "vendor_sku" },
        "ean": { "path": "barcode" },
        "name": { "path": "title", "template": "{{ value|trim }}" },
        "quantity": { "path": "stock/total", "template": "{{ value|default(0)|round }}" },
        "price": { "path": "pricing/base_price", "template": "{{ value|float }}" },
        "currency": { "path": "pricing/currency_code", "template": "{{ value|upper|default('PLN') }}" },
        "tax": { "path": "pricing/vat_rate", "template": "{{ value|round }}" },
        "weight": { "path": "dimensions/weight_kg", "template": "{{ value|float }}" },
        "height": { "path": "dimensions/h", "template": "{{ value|float }}" },
        "length": { "path": "dimensions/l", "template": "{{ value|float }}" },
        "width": { "path": "dimensions/w", "template": "{{ value|float }}" },
        "description": { "path": "content/html_description", "template": "{{ value|trim }}" },
        "description_extra1": { "path": "content/short_description" },
        "description_extra2": { "path": "content/technical_specs" },
        "description_extra3": { "path": "content/warranty_info" },
        "description_extra4": { "path": "content/shipping_notes" },
        "man_name": { "path": "brand/name" },
        "location": { "path": "warehouse/bin_location" },
        "url": { "path": "seo/canonical_url" },
        "delivery_time": { "path": "logistics/lead_days", "template": "{{ value|default(1)|round }}" },
        "images": {
            "path": "gallery/photo",
            "template": "{{ value|split(',')|map(img => img|trim)|json_encode() }}"
        },
        "categories": {
            "path": "taxonomy/group",
            "mappings": {
                "id": { "path": "@id" },
                "name": { "path": "label" }
            }
        },
        "features": {
            "path": "attributes/attr",
            "mappings": {
                "name": { "path": "key" },
                "value": { "path": "val" }
            }
        },
        "variants": {
            "path": "model_variants/variant",
            "mappings": {
                "id": { "path": "@id" },
                "sku": { "path": "v_sku" },
                "ean": { "path": "v_ean" },
                "name": { "path": "v_name" },
                "full_name": { "path": "v_display_name", "template": "{{ value|default(raw.name ~ ' - ' ~ name) }}" },
                "price": { "path": "v_price", "template": "{{ value|default(raw.price)|float }}" },
                "quantity": { "path": "v_stock", "template": "{{ value|default(0)|round }}" },
                "weight": { "path": "v_weight", "template": "{{ value|default(raw.weight)|float }}" },
                "height": { "path": "v_height" },
                "length": { "path": "v_length" },
                "width": { "path": "v_width" },
                "tax": { "path": "v_vat" },
                "images": { "path": "v_images", "template": "{{ value|split(',')|json_encode() }}" },
                "features": { "path": "v_attrs", "template": "{{ value|split(';')|map(f => f|split(':'))|json_encode() }}" }
            }
        }
    }
}
```

---

## 🏗 Admin Interface

Access the administrative interface at `/admin`.
- **Integrations**: Manage feed URLs, schedules (Cron), and import definitions.
- **Product Preview**: View synced products with history tracking and full-width tabbed layout.
- **Logs & Alerts**: Monitor synchronization health and set up notifications for price/stock changes.

---

## 📄 License

The BaseConnector project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
