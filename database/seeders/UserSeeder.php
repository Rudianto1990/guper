<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('admin123'),
            'roles' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        DB::table('users')->insert([
            'name' => 'fatur',
            'email' => 'fatur@mail.com',
            'password' => Hash::make('fatur123'),
            'roles' => 'user',
            'nip' => '1234567890',
        ]);

        DB::table('users')->insert([
            'name' => 'rudi',
            'email' => 'rudi@mail.com',
            'password' => Hash::make('rudi123'),
            'roles' => 'petugas',
            'nip' => '0987654321',
        ]);

        DB::table('users')->insert([
            'name' => 'Rias',
            'email' => 'rias@mail.com',
            'password' => Hash::make('rias123'),
            'roles' => 'sepervisior',
            'nip' => '123454321',
        ]);

        
        }
    }
}
