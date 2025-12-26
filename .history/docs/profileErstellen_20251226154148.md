cd /home/u854179217/domains/christianresch.esy.es/public_html/martin

php artisan tinker

use App\Models\User;

User::create([
    'name' => 'Petra',
    'email' => 'Petra.resch.com',
    'password' => bcrypt('petraResch'),
    'email_verified_at' => now(),
]);