<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;
use Modules\Directory\Traits\HasSuggestions;
use Tests\TestCase;

class HasSuggestionsTest extends TestCase
{
    public function test_suggestable_fields_returns_expected_array(): void
    {
        $tool = new Tool;
        $fields = $tool->suggestableFields();

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('short_description', $fields);
        $this->assertArrayHasKey('pricing', $fields);
        $this->assertArrayHasKey('url', $fields);
        $this->assertEquals('Description', $fields['description']);
    }

    public function test_suggestable_field_validation_returns_in_rule(): void
    {
        $tool = new Tool;
        $rule = $tool->suggestableFieldValidation();

        $this->assertIsString($rule);
        $this->assertTrue(Str::startsWith($rule, 'in:'));
        $this->assertStringContainsString('description', $rule);
        $this->assertStringContainsString('pricing', $rule);
        $this->assertStringContainsString('url', $rule);
    }

    public function test_suggestions_relation_is_morph_many(): void
    {
        $tool = new Tool;
        $relation = $tool->suggestions();

        $this->assertInstanceOf(MorphMany::class, $relation);
    }

    public function test_model_without_suggestable_fields_returns_empty(): void
    {
        $model = new class extends Model
        {
            use HasSuggestions;

            protected $table = 'test_dummy';
        };

        $this->assertIsArray($model->suggestableFields());
        $this->assertEmpty($model->suggestableFields());
        $this->assertEquals('in:', $model->suggestableFieldValidation());
    }
}
