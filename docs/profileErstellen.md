cd /home/u854179217/domains/christianresch.esy.es/public_html/martin

php artisan tinker

use App\Models\User;

User::create([
    'name' => 'Martin',
    'email' => 'resch.kg@gmail.com',
    'password' => bcrypt('martinResch'),
    'email_verified_at' => now(),
]);