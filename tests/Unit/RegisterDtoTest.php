<?php

namespace App\Tests\Unit;

use App\Dto\RegisterDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class RegisterDtoTest extends TestCase
{
    private function validate(string $email, string $password, string $firstName = 'Alice', string $lastName = 'Martin'): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dto            = new RegisterDto();
        $dto->firstName = $firstName;
        $dto->lastName  = $lastName;
        $dto->email     = $email;
        $dto->password  = $password;

        return iterator_to_array($validator->validate($dto));
    }

    // ── Valid cases ───────────────────────────────────────────────────────────

    public function testValidEmailAndPassword(): void
    {
        $violations = $this->validate('user@example.com', 'Password1');
        $this->assertCount(0, $violations);
    }

    public function testMissingFirstNameFails(): void
    {
        $violations = $this->validate('user@example.com', 'Password1', '', 'Martin');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testMissingLastNameFails(): void
    {
        $violations = $this->validate('user@example.com', 'Password1', 'Alice', '');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    // ── Email validation ──────────────────────────────────────────────────────

    public function testBlankEmailFails(): void
    {
        $violations = $this->validate('', 'Password1');
        $this->assertGreaterThanOrEqual(1, count($violations));
        $messages = array_map(fn ($v) => $v->getMessage(), $violations);
        $found = array_filter($messages, fn ($m) => str_contains($m, 'email') || str_contains($m, 'obligatoire'));
        $this->assertNotEmpty($found);
    }

    public function testInvalidEmailFails(): void
    {
        $violations = $this->validate('not-an-email', 'Password1');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testEmailTooLongFails(): void
    {
        $long = str_repeat('a', 176) . '@b.com'; // 182 chars > 180
        $violations = $this->validate($long, 'Password1');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    // ── Password validation ───────────────────────────────────────────────────

    public function testBlankPasswordFails(): void
    {
        $violations = $this->validate('user@example.com', '');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testPasswordTooShortFails(): void
    {
        $violations = $this->validate('user@example.com', 'Pass1'); // 5 chars
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testPasswordWithoutUppercaseFails(): void
    {
        $violations = $this->validate('user@example.com', 'password1');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testPasswordWithoutDigitFails(): void
    {
        $violations = $this->validate('user@example.com', 'Password');
        $this->assertGreaterThanOrEqual(1, count($violations));
    }
}
