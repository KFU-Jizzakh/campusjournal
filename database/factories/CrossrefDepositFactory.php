<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\CrossrefDeposit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CrossrefDeposit> */
class CrossrefDepositFactory extends Factory
{
    protected $model = CrossrefDeposit::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'doi' => '10.12345/'.fake()->uuid(),
            'batch_id' => fake()->uuid(),
            'xml_payload' => fake()->paragraph(),
            'status' => CrossrefDeposit::STATUS_PENDING,
            'http_status' => null,
            'response_body' => null,
            'error' => null,
            'attempted_by' => null,
        ];
    }
}
