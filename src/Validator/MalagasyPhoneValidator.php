<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Validator;

use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class MalagasyPhoneValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MalagasyPhone) {
            throw new UnexpectedTypeException($constraint, MalagasyPhone::class);
        }
        if ($value === null || $value === '') {
            return;
        }
        $phone = $value instanceof PhoneNumber ? $value : PhoneNumber::tryParse($value);
        if ($phone !== null && ($constraint->type === null || $phone->type === $constraint->type)) {
            return;
        }
        $this->context->buildViolation($constraint->messageFor($constraint->type))
            ->setCode($phone === null ? MalagasyPhone::INVALID_ERROR : MalagasyPhone::WRONG_TYPE_ERROR)
            ->addViolation();
    }
}
