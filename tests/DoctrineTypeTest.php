<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Phone\Operator;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtilsBundle\Doctrine\AriaryType;
use Mdgascar\MdgUtilsBundle\Doctrine\PhoneNumberType;
use PHPUnit\Framework\Attributes\DataProvider;

final class DoctrineTypeTest extends KernelTestCase
{
    private const string E164 = '+261341234567';
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.2;
    private const int PRECISION = 20;
    private const int SCALE = 2;
    private const string DECIMAL_TEXT = '1500.2';
    // How most drivers return DECIMAL and integer columns.
    private const string DECIMAL_COLUMN_TEXT = '1500.20';
    private const string DECIMAL_DIGITS = '1500.2';
    // More digits than a float holds: the column keeps them, and so does the type.
    private const string EXACT_COLUMN_TEXT = '2000000000000000.9';
    private const string INTEGER_TEXT = '1500';
    // 031 is not assigned to any operator.
    private const string UNKNOWN_OPERATOR_E164 = '+261311234567';
    private const string NEGATIVE_TEXT = '-1';

    private SQLitePlatform $platform;

    protected function setUp(): void
    {
        // Creating the connection registers the bundle's types; nothing connects to a database.
        $this->service($this->boot(), Connection::class);
        $this->platform = new SQLitePlatform();
    }

    public function testTheBundleRegistersBothTypes(): void
    {
        self::assertInstanceOf(PhoneNumberType::class, Type::getType(PhoneNumberType::NAME));
        self::assertInstanceOf(AriaryType::class, Type::getType(AriaryType::NAME));
    }

    public function testAPhoneNumberIsStoredAsE164Text(): void
    {
        $type = Type::getType(PhoneNumberType::NAME);

        self::assertSame('VARCHAR(16)', $type->getSQLDeclaration([], $this->platform));
        self::assertSame(self::E164, $type->convertToDatabaseValue(PhoneNumber::parse('034 12 345 67'), $this->platform));
        $phone = $type->convertToPHPValue(self::E164, $this->platform);
        self::assertInstanceOf(PhoneNumber::class, $phone);
        self::assertSame(Operator::Yas, $phone->operator);
        self::assertNull($type->convertToPHPValue(null, $this->platform));
        self::assertNull($type->convertToDatabaseValue(null, $this->platform));
    }

    public function testAnAriaryIsStoredInADecimalColumn(): void
    {
        $type = Type::getType(AriaryType::NAME);

        self::assertSame(
            'NUMERIC(20, 2)',
            $type->getSQLDeclaration(['precision' => self::PRECISION, 'scale' => self::SCALE], $this->platform),
        );
        self::assertSame(self::DECIMAL_TEXT, $type->convertToDatabaseValue(Ariary::fromFloat(self::DECIMAL_AMOUNT), $this->platform));
        self::assertNull($type->convertToDatabaseValue(null, $this->platform));
        self::assertNull($type->convertToPHPValue(null, $this->platform));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function storedAmounts(): iterable
    {
        yield 'decimal column text' => [self::DECIMAL_COLUMN_TEXT, self::DECIMAL_DIGITS];
        yield 'integer text' => [self::INTEGER_TEXT, self::INTEGER_TEXT];
        yield 'an int from the driver' => [self::AMOUNT, self::INTEGER_TEXT];
        yield 'a float from the driver' => [self::DECIMAL_AMOUNT, self::DECIMAL_DIGITS];
        yield 'a decimal column beyond any float' => [self::EXACT_COLUMN_TEXT, self::EXACT_COLUMN_TEXT];
    }

    #[DataProvider('storedAmounts')]
    public function testAStoredAmountIsReadAsAnAriaryWithoutRounding(mixed $stored, string $digits): void
    {
        $ariary = Type::getType(AriaryType::NAME)->convertToPHPValue($stored, $this->platform);

        self::assertInstanceOf(Ariary::class, $ariary);
        self::assertSame($digits, $ariary->digits);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function unreadableValues(): iterable
    {
        yield 'phone, not an operator' => [PhoneNumberType::NAME, self::UNKNOWN_OPERATOR_E164];
        yield 'phone, not text' => [PhoneNumberType::NAME, self::AMOUNT];
        yield 'ariary, not a number' => [AriaryType::NAME, 'Ar 1 500'];
        yield 'ariary, negative text' => [AriaryType::NAME, self::NEGATIVE_TEXT];
        yield 'ariary, a negative int from the driver' => [AriaryType::NAME, -self::AMOUNT];
    }

    #[DataProvider('unreadableValues')]
    public function testAStoredValueThatIsNotValidCannotBeRead(string $type, mixed $value): void
    {
        $this->expectException(ValueNotConvertible::class);

        Type::getType($type)->convertToPHPValue($value, $this->platform);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function typeNames(): iterable
    {
        yield 'phone' => [PhoneNumberType::NAME];
        yield 'ariary' => [AriaryType::NAME];
    }

    #[DataProvider('typeNames')]
    public function testOnlyTheValueObjectCanBeStored(string $type): void
    {
        $this->expectException(InvalidType::class);

        Type::getType($type)->convertToDatabaseValue('034 12 345 67', $this->platform);
    }

    public function testWithoutDoctrineTheAppStillBoots(): void
    {
        self::assertFalse($this->boot([], minimal: true)->has('doctrine'));
    }
}
