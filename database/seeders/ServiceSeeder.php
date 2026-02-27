<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::updateOrCreate(
            ['slug' => 'bacen'],
            [
                'nome' => 'Regularização Bacen',
                'descricao' => 'Suporte para regularização de pendências no Bacen.',
                'icone' => 'bacen',
                'preco' => 199.90,
                'ativo' => true,
            ]
        );

        Service::updateOrCreate(
            ['slug' => 'serasa'],
            [
                'nome' => 'Regularização Serasa',
                'descricao' => 'Recuperação de crédito e negociação de pendências no Serasa.',
                'icone' => 'serasa',
                'preco' => 149.90,
                'ativo' => true,
            ]
        );

        Service::updateOrCreate(
            ['slug' => 'cnh'],
            [
                'nome' => 'Regularização CNH',
                'descricao' => 'Acompanhamento para regularização de situação da CNH.',
                'icone' => 'cnh',
                'preco' => 99.90,
                'ativo' => true,
            ]
        );
    }
}
