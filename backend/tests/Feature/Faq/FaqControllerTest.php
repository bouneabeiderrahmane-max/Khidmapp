<?php

namespace Tests\Feature\Faq;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_faqs_are_publicly_listed_ordered_by_position(): void
    {
        Faq::query()->create(['question' => ['fr' => 'Q2', 'ar' => 'س2'], 'answer' => ['fr' => 'R2', 'ar' => 'ج2'], 'position' => 2]);
        Faq::query()->create(['question' => ['fr' => 'Q1', 'ar' => 'س1'], 'answer' => ['fr' => 'R1', 'ar' => 'ج1'], 'position' => 1]);

        $response = $this->getJson('/api/v1/faqs')->assertOk();

        $this->assertSame('Q1', $response->json('data.0.question.fr'));
        $this->assertSame('Q2', $response->json('data.1.question.fr'));
    }
}
