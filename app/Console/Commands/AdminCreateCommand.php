<?php

namespace App\Console\Commands;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AdminCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively create the first administrator (root) account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->ask('Administrator name');
        $phone = $this->ask('Administrator phone (unique login identifier)');
        $email = $this->ask('Administrator email');
        $password = $this->secret('Administrator password (min 8 characters)');
        $passwordConfirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            $this->error('Could not create administrator:');
            foreach ($validator->errors()->all() as $message) {
                $this->line("- {$message}");
            }

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $admin->assignRole(Roles::ROOT->value);

        $this->info("Administrator '{$name}' created successfully with the root role.");

        return self::SUCCESS;
    }
}
