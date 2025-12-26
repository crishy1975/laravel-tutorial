use App\Models\User;

User::create([
    'name' => 'Martin',
    'email' => 'christian@example.com',
    'password' => bcrypt('DeinPasswort123'),
    'email_verified_at' => now(),
]);