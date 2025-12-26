use App\Models\User;

User::create([
    'name' => 'Martin',
    'email' => 'christian@example.com',
    'password' => bcrypt('martinResch'),
    'email_verified_at' => now(),
]);