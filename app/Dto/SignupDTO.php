<?php

declare(strict_types=1);

namespace App\Dto;

final class SignupDTO
{
    public function __construct(
        public string $email = '',
        public string $password = '',
        public string $confirmPassword = '',
        public string $fullName = '',
        public string $company = '',
        public string $role = '',
        public bool   $newsletter = false,
        public string $plan = 'basic',
        public bool   $terms = false,
    ) {
    }

    public static function fromArray(array $a): self
    {
        return new self(
            (string)($a['email'] ?? ''),
            (string)($a['password'] ?? ''),
            (string)($a['confirmPassword'] ?? ''),
            (string)($a['fullName'] ?? ''),
            (string)($a['company'] ?? ''),
            (string)($a['role'] ?? ''),
            (bool)($a['newsletter'] ?? false),
            (string)($a['plan'] ?? 'basic'),
            (bool)($a['terms'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'confirmPassword' => $this->confirmPassword,
            'fullName' => $this->fullName,
            'company' => $this->company,
            'role' => $this->role,
            'newsletter' => $this->newsletter,
            'plan' => $this->plan,
            'terms' => $this->terms,
        ];
    }
}
