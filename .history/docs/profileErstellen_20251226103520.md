

use App\Models\User;

User::create([
    'name' => 'Martin',
    'email' => 'resch.kg@gmail.com',
    'password' => bcrypt('martinResch'),
    'email_verified_at' => now(),
]);