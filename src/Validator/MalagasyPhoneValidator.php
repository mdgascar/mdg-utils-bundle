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
        if ($value === null || $value === '' || $value instanceof PhoneNumber || PhoneNumber::isValid($value)) {
            return;
        }
        $this->context->buildViolation($constraint->message)
            ->setCode(MalagasyPhone::INVALID_ERROR)
            ->addViolation();
    }
}
