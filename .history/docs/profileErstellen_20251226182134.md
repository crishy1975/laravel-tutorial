cd /home/u854179217/domains/christianresch.esy.es/public_html/martin

php artisan tinker

use App\Models\User;

User::create([
    'name' => 'Barbara',
    'email' => 'jakobresch000@gmail.com ',
    'password' => bcrypt('barbaraWild'),
    'email_verified_at' => now(),
]);