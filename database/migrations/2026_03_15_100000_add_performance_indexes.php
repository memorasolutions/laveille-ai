<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->getIndexDefinitions() as $definition) {
            try {
                if (! Schema::hasTable($definition['table'])) {
                    continue;
                }

                foreach ($definition['columns'] as $column) {
                    if (! Schema::hasColumn($definition['table'], $column)) {
                        continue 2;
                    }
                }

                Schema::table($definition['table'], function (Blueprint $table) use ($definition) {
                    $table->index($definition['columns'], $definition['name']);
                });
            } catch (\Exception) {
                // Index may already exist or module not installed — skip silently
            }
        }
    }

    public function down(): void
    {
        foreach ($this->getIndexDefinitions() as $definition) {
            try {
                if (! Schema::hasTable($definition['table'])) {
                    continue;
                }

                Schema::table($definition['table'], function (Blueprint $table) use ($definition) {
                    $table->dropIndex($definition['name']);
                });
            } catch (\Exception) {
                // Index may not exist — skip silently
            }
        }
    }

    /** @return array<int, array{table: string, columns: array<string>, name: string}> */
    private function getIndexDefinitions(): array
    {
        $single = fn (string $table, string $column) => [
            'table' => $table,
            'columns' => [$column],
            'name' => "{$table}_{$column}_index",
        ];

        $composite = fn (string $table, array $columns) => [
            'table' => $table,
            'columns' => $columns,
            'name' => "{$table}_".implode('_', $columns).'_index',
        ];

        return [
            // Pivot tables — FK without auto-index
            $single('article_tag', 'article_id'),
            $single('article_tag', 'tag_id'),
            $single('ecommerce_product_category', 'product_id'),
            $single('ecommerce_product_category', 'category_id'),
            $single('ecommerce_attribute_value_variant', 'attribute_value_id'),
            $single('ecommerce_attribute_value_variant', 'variant_id'),

            // Status/filter columns
            $single('newsletter_campaigns', 'status'),
            $single('ecommerce_products', 'is_active'),
            $single('ecommerce_product_variants', 'is_active'),
            $single('ecommerce_coupons', 'is_active'),
            $single('ecommerce_coupons', 'expires_at'),

            // FK on cart/order items
            $single('ecommerce_cart_items', 'cart_id'),
            $single('ecommerce_cart_items', 'product_id'),
            $single('ecommerce_cart_items', 'variant_id'),
            $single('ecommerce_order_items', 'order_id'),
            $single('ecommerce_order_items', 'product_id'),
            $single('ecommerce_order_items', 'variant_id'),

            // Workflow FK
            $single('workflow_enrollments', 'workflow_id'),
            $single('workflow_enrollments', 'user_id'),
            $single('workflow_step_logs', 'enrollment_id'),

            // Roadmap
            $single('roadmap_ideas', 'status'),
            $composite('roadmap_ideas', ['board_id', 'status']),
            $single('roadmap_idea_comments', 'user_id'),

            // Multi-tenant
            $single('url_redirects', 'tenant_id'),
        ];
    }
};
