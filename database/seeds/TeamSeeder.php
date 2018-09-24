<?php

use App\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Team::class)->create(['name' => 'nexora']);

        factory(Team::class)->times(99)->create();
    }
}
