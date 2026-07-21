<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPasswords extends Command
{
    protected $signature = 'users:reset-passwords {emails?* : Specific emails to reset (default: all from UserSeeder)}';

    protected $description = 'Reset passwords for users defined in UserSeeder and output them';

    public function handle(): int
    {
        $defaultEmails = [
            'admin@globalcampus.local',
            'galimov@globalcampus.local',
            'managing@globalcampus.local',
            'section@globalcampus.local',
            'reviewer@globalcampus.local',
            'author@globalcampus.local',
            'reviewer2@globalcampus.local',
            'author2@globalcampus.local',
        ];

        $emails = $this->argument('emails') ?: $defaultEmails;
        $results = [];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->warn("User not found: {$email}");

                continue;
            }

            $password = Str::password(16, true, true, true, false);
            $user->update(['password' => Hash::make($password)]);

            $results[] = ['email' => $email, 'password' => $password];
        }

        if (empty($results)) {
            $this->warn('No users were updated.');

            return self::FAILURE;
        }

        $this->table(['Email', 'Password'], $results);

        return self::SUCCESS;
    }
}
