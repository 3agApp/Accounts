<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Creates an account. There is no public registration, so this is the only
 * way in.
 */
class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:create-user
                            {email : The email address of the new account}
                            {--name= : The person\'s name}
                            {--grant=* : Clients to grant access to, by name or id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a 3AG Accounts user and email them a link to set their password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: $this->ask('Name'));

        $validator = Validator::make(['email' => $email, 'name' => $name], [
            'email' => ['required', 'email', Rule::unique(User::class, 'email')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(32),
        ]);

        foreach ($this->option('grant') as $identifier) {
            $this->call('accounts:grant', ['email' => $user->email, 'client' => $identifier]);
        }

        $user->sendEmailVerificationNotification();

        Password::sendResetLink(['email' => $user->email]);

        $this->components->info("Created {$user->email}. A link to set their password is on its way.");

        return self::SUCCESS;
    }
}
