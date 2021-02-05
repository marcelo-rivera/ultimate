<?php

use Illuminate\Database\Seeder;

class CoresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('cores')->insert(['descricao' => 'Branco',]);
        \DB::table('cores')->insert(['descricao' => 'Preto',]);
        \DB::table('cores')->insert(['descricao' => 'Cinza',]);
        \DB::table('cores')->insert(['descricao' => 'Vermelho',]);
        \DB::table('cores')->insert(['descricao' => 'Verde',]);
        \DB::table('cores')->insert(['descricao' => 'Amarelo',]);
        \DB::table('cores')->insert(['descricao' => 'Roxo',]);
        \DB::table('cores')->insert(['descricao' => 'Rosa',]);
        \DB::table('cores')->insert(['descricao' => 'Laranja',]);
        \DB::table('cores')->insert(['descricao' => 'Prata',]);
        \DB::table('cores')->insert(['descricao' => 'Chumbo',]);
        \DB::table('cores')->insert(['descricao' => 'Grafite',]);
        \DB::table('cores')->insert(['descricao' => 'Cinza',]);
        \DB::table('cores')->insert(['descricao' => 'Marrom',]);
        \DB::table('cores')->insert(['descricao' => 'Dourado',]);
        \DB::table('cores')->insert(['descricao' => 'Mostarda',]);
        \DB::table('cores')->insert(['descricao' => 'Caramelo',]);
        \DB::table('cores')->insert(['descricao' => 'Bege',]);
        \DB::table('cores')->insert(['descricao' => 'Azul',]);
    }
}
