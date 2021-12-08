<?php

namespace Database\Factories;

use App\Models\Visitante;
use App\Models\Agendamento;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
          'cpf' => $this->faker->ean8(),
          'nome' => $this->faker->name(),
          'telefone' => $this->faker->phoneNumber(),
          'email' => $this->faker->email(),
          'endereco' => $this->faker->streetName()
        ];
    }
}
