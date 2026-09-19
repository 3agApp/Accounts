<?php

namespace App\Oidc;

use Laravel\Passport\Passport;
use RuntimeException;

/**
 * The RSA key pair Passport signs with, expressed in the shapes OIDC needs.
 *
 * Both the ID token header and the published JWKS take their `kid` from here,
 * so a relying party can never be handed a token it cannot find a key for.
 */
class SigningKey
{
    protected ?string $privateKey = null;

    protected ?string $publicKey = null;

    protected ?string $keyId = null;

    /**
     * Get the PEM encoded private key used to sign ID tokens.
     */
    public function privateKey(): string
    {
        return $this->privateKey ??= $this->readKey('private');
    }

    /**
     * Get the PEM encoded public key relying parties verify with.
     */
    public function publicKey(): string
    {
        return $this->publicKey ??= $this->readKey('public');
    }

    /**
     * Get the RFC 7638 thumbprint that identifies the key.
     */
    public function keyId(): string
    {
        if (isset($this->keyId)) {
            return $this->keyId;
        }

        ['n' => $modulus, 'e' => $exponent] = $this->parameters();

        $canonical = sprintf('{"e":"%s","kty":"RSA","n":"%s"}', $exponent, $modulus);

        return $this->keyId = $this->base64UrlEncode(hash('sha256', $canonical, true));
    }

    /**
     * Get the public key as a JSON Web Key.
     *
     * @return array{kty: string, use: string, alg: string, kid: string, n: string, e: string}
     */
    public function jsonWebKey(): array
    {
        ['n' => $modulus, 'e' => $exponent] = $this->parameters();

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $this->keyId(),
            'n' => $modulus,
            'e' => $exponent,
        ];
    }

    /**
     * Get the base64url encoded RSA modulus and exponent of the public key.
     *
     * @return array{n: string, e: string}
     */
    protected function parameters(): array
    {
        $key = openssl_pkey_get_public($this->publicKey());

        if ($key === false) {
            throw new RuntimeException('The Passport public key is not a valid PEM encoded key.');
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new RuntimeException('The Passport public key must be an RSA key to sign ID tokens.');
        }

        return [
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];
    }

    /**
     * Read a key from the environment, falling back to Passport's key path.
     */
    protected function readKey(string $type): string
    {
        $key = str_replace('\\n', "\n", (string) config("passport.{$type}_key"));

        if ($key !== '') {
            return $key;
        }

        $path = Passport::keyPath('oauth-'.$type.'.key');

        if (! is_readable($path)) {
            throw new RuntimeException(
                "Unable to read the Passport {$type} key. Run [php artisan passport:keys] or set the PASSPORT_".strtoupper($type).'_KEY environment variable.'
            );
        }

        return (string) file_get_contents($path);
    }

    /**
     * Encode the given bytes without padding, as JOSE requires.
     */
    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
