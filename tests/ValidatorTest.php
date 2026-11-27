<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Phone\PhoneLineType;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtilsBundle\AriaryOptions;
use Mdgascar\MdgUtilsBundle\Validator\AriaryAmount;
use Mdgascar\MdgUtilsBundle\Validator\AriaryAmountValidator;
use Mdgascar\MdgUtilsBundle\Validator\MalagasyPhone;
use Mdgascar\MdgUtilsBundle\Validator\MalagasyPhoneValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidatorTest extends KernelTestCase
{
    private const string VALID_PHONE = '034 12 345 67';
    private const string FIXED_LINE_PHONE = '020 22 421 19';
    // 031 is not assigned to any operator.
    private const string UNKNOWN_OPERATOR_PHONE = '031 12 345 67';
    private const int JSON_AMOUNT = 1500;
    // A dot is not a decimal point by default.
    private const string DOT_DECIMAL_TEXT = '1.5';

    /**
     * @return iterable<string, array{Constraint, mixed, bool}>
     */
    public static function cases(): iterable
    {
        yield 'phone, valid text' => [new MalagasyPhone(), self::VALID_PHONE, true];
        yield 'phone, international' => [new MalagasyPhone(), '+261 34 12 345 67', true];
        yield 'phone, a PhoneNumber' => [new MalagasyPhone(), PhoneNumber::parse(self::VALID_PHONE), true];
        yield 'phone, null' => [new MalagasyPhone(), null, true];
        yield 'phone, empty' => [new MalagasyPhone(), '', true];
        yield 'phone, unknown operator' => [new MalagasyPhone(), self::UNKNOWN_OPERATOR_PHONE, false];
        yield 'phone, not text' => [new MalagasyPhone(), [self::VALID_PHONE], false];
        yield 'phone, fixed line' => [new MalagasyPhone(), self::FIXED_LINE_PHONE, true];
        yield 'mobile, mobile' => [new MalagasyPhone(type: PhoneLineType::Mobile), self::VALID_PHONE, true];
        yield 'mobile, fixed line' => [new MalagasyPhone(type: PhoneLineType::Mobile), self::FIXED_LINE_PHONE, false];
        yield 'fixed, mobile' => [new MalagasyPhone(type: PhoneLineType::Fixed), self::VALID_PHONE, false];
        yield 'ariary, text' => [new AriaryAmount(), 'Ar 1 500,50', true];
        yield 'ariary, JSON number' => [new AriaryAmount(), self::JSON_AMOUNT, true];
        yield 'ariary, an Ariary' => [new AriaryAmount(), Ariary::fromInt(self::JSON_AMOUNT), true];
        yield 'ariary, null' => [new AriaryAmount(), null, true];
        yield 'ariary, negative' => [new AriaryAmount(), -self::JSON_AMOUNT, false];
        yield 'ariary, a dot is not a decimal point' => [new AriaryAmount(), self::DOT_DECIMAL_TEXT, false];
    }

    #[DataProvider('cases')]
    public function testEachConstraintPassesOrFails(Constraint $constraint, mixed $value, bool $passes): void
    {
        self::assertSame($passes, $this->validate($value, $constraint)->count() === 0);
    }

    public function testANumberOfTheWrongKindHasItsOwnMessageAndCode(): void
    {
        $violation = $this->validate(self::FIXED_LINE_PHONE, new MalagasyPhone(type: PhoneLineType::Mobile))->get(0);

        self::assertSame('This value is not a valid Malagasy mobile number.', $violation->getMessage());
        self::assertSame(MalagasyPhone::WRONG_TYPE_ERROR, $violation->getCode());
    }

    public function testAViolationHasTheMessageAndTheCode(): void
    {
        $violation = $this->validate(self::UNKNOWN_OPERATOR_PHONE, new MalagasyPhone())->get(0);

        self::assertSame('This value is not a valid Malagasy phone number.', $violation->getMessage());
        self::assertSame(MalagasyPhone::INVALID_ERROR, $violation->getCode());
        self::assertSame('INVALID_MALAGASY_PHONE_ERROR', MalagasyPhone::getErrorName(MalagasyPhone::INVALID_ERROR));
    }

    public function testACustomMessageReplacesTheDefault(): void
    {
        $violation = $this->validate('abc', new AriaryAmount(message: 'Bad amount.'))->get(0);

        self::assertSame('Bad amount.', $violation->getMessage());
        self::assertSame(AriaryAmount::INVALID_ERROR, $violation->getCode());
    }

    /**
     * @return iterable<string, array{Constraint, string}>
     */
    public static function frenchMessages(): iterable
    {
        yield 'phone' => [new MalagasyPhone(), 'Cette valeur n\'est pas un numéro de téléphone malgache valide.'];
        yield 'mobile' => [new MalagasyPhone(type: PhoneLineType::Mobile), 'Cette valeur n\'est pas un numéro de mobile malgache valide.'];
        yield 'ariary' => [new AriaryAmount(), 'Cette valeur n\'est pas un montant en ariary valide.'];
    }

    #[DataProvider('frenchMessages')]
    public function testMessagesAreTranslatedToFrench(Constraint $constraint, string $message): void
    {
        $container = $this->boot();
        $this->service($container, LocaleSwitcher::class)->setLocale('fr');
        $violations = $this->service($container, ValidatorInterface::class)->validate('abc', $constraint);

        self::assertSame($message, $violations->get(0)->getMessage());
    }

    public function testTheAriaryConstraintReadsWithTheConfiguredConvention(): void
    {
        $validator = $this->service(
            $this->boot(['ariary' => ['grouping' => 'comma', 'decimal' => 'dot']]),
            ValidatorInterface::class,
        );

        self::assertCount(0, $validator->validate('1,500.50', new AriaryAmount()));
        self::assertCount(1, $validator->validate('1 500,50', new AriaryAmount()));
    }

    /**
     * @return iterable<string, array{ConstraintValidator}>
     */
    public static function validators(): iterable
    {
        yield 'phone' => [new MalagasyPhoneValidator()];
        yield 'ariary' => [new AriaryAmountValidator(new AriaryOptions())];
    }

    #[DataProvider('validators')]
    public function testAValidatorRefusesAnotherConstraint(ConstraintValidator $validator): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $validator->validate('x', new NotBlank());
    }

    public function testConstraintsWorkWithoutTwigOrDoctrine(): void
    {
        $validator = $this->service($this->boot([], minimal: true), ValidatorInterface::class);

        self::assertCount(1, $validator->validate(self::UNKNOWN_OPERATOR_PHONE, new MalagasyPhone()));
    }

    private function validate(mixed $value, Constraint $constraint): ConstraintViolationListInterface
    {
        return $this->service($this->boot(), ValidatorInterface::class)->validate($value, $constraint);
    }
}
