<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Deliberately without the `WithoutModelEvents` trait: the User model
     * assigns its `public_id` — the OIDC subject every product stores — on the
     * `creating` event, and silencing model events here would seed accounts
     * that no client can identify.
     */
    public function run(): void
    {
        $this->call(ClientSeeder::class);

        if (! app()->isLocal()) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->clients()->sync(
            Client::query()->pluck('id')
                ->mapWithKeys(fn (string $id): array => [$id => ['granted_at' => now()]])
                ->all()
        );

        $this->command?->newLine();
        $this->command?->line('  <fg=green>test@example.com</> can sign in to every product (password: <fg=gray>password</>)');
    }
}
