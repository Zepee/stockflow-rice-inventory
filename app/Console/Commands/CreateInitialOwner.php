<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateInitialOwner extends Command
{
    protected $signature = 'stockflow:create-initial-owner';

    protected $description = 'Create the first StockFlow Owner account securely';

    public function handle(): int
    {
        if (User::query()->where('role', UserRole::Owner->value)->exists()) {
            $this->error('Akun Owner sudah tersedia.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama Owner');
        $email = $this->ask('Email Owner');
        $password = $this->secret('Kata sandi Owner');
        $confirmation = $this->secret('Ulangi kata sandi Owner');

        try {
            $data = Validator::make(
                [
                    'name' => $name,
                    'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
                    'password' => $password,
                ],
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'string', Password::defaults()],
                ],
            )->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        if (! is_string($confirmation) || ! is_string($password) || ! hash_equals($password, $confirmation)) {
            $this->error('Kata sandi dan konfirmasi tidak sama.');

            return self::FAILURE;
        }

        $owner = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Owner,
        ]);

        $this->info("Akun Owner berhasil dibuat untuk {$owner->email}.");

        return self::SUCCESS;
    }
}
