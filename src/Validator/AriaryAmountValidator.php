<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Validator;

use Mdgascar\MdgUtilsBundle\AriaryOptions;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AriaryAmountValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AriaryOptions $options,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AriaryAmount) {
            throw new UnexpectedTypeException($constraint, AriaryAmount::class);
        }
        if ($value === null || $value === '' || $this->options->tryRead($value) !== null) {
            return;
        }
        $this->context->buildViolation($constraint->message)
            ->setCode(AriaryAmount::INVALID_ERROR)
            ->addViolation();
    }
}
