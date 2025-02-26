<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Import the Log facade

class HashUserPassword extends Command
{
    protected $signature = 'user:hash-password {user_id}';
    protected $description = 'Hash the password for a user with the given user ID';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::where('user_id', $userId)->first();

        if (!$user) {
            $this->error('User not found.');
            return;
        }

        $newPassword = '1'; // Set the new password here
        $user->password = Hash::make($newPassword);
        
        // Log the query for debugging
        DB::enableQueryLog();
        $user->save();
        $queries = DB::getQueryLog();
        Log::info('Queries executed:', $queries);

        $this->info('Password hashed and updated successfully for user ID: ' . $userId);
    }
}
