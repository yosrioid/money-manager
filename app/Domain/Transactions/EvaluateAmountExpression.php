<?php

namespace App\Domain\Transactions;

use LogicException;

class EvaluateAmountExpression
{
    private string $expression = '';

    private int $position = 0;

    public function evaluate(string|int $expression): int
    {
        $this->expression = preg_replace('/\s+/', '', (string) $expression) ?? '';
        $this->position = 0;

        if ($this->expression === '' || preg_match('/[^0-9+\-*\/()]/', $this->expression)) {
            throw new LogicException('The amount expression is invalid.');
        }

        $result = $this->parseExpression();

        if ($this->position !== strlen($this->expression) || $result < 1) {
            throw new LogicException('The amount expression must resolve to a positive integer.');
        }

        return $result;
    }

    /** @phpstan-impure */
    private function parseExpression(): int
    {
        $value = $this->parseTerm();

        while (in_array($this->current(), ['+', '-'], true)) {
            $operator = $this->consume();
            $operand = $this->parseTerm();
            $value = $operator === '+' ? $value + $operand : $value - $operand;
            $this->assertInteger($value);
        }

        return $value;
    }

    /** @phpstan-impure */
    private function parseTerm(): int
    {
        $value = $this->parseFactor();

        while (in_array($this->current(), ['*', '/'], true)) {
            $operator = $this->consume();
            $operand = $this->parseFactor();

            if ($operator === '/' && ($operand === 0 || $value % $operand !== 0)) {
                throw new LogicException('Division must produce a whole number.');
            }

            $value = $operator === '*' ? $value * $operand : intdiv($value, $operand);
            $this->assertInteger($value);
        }

        return $value;
    }

    /** @phpstan-impure */
    private function parseFactor(): int
    {
        if ($this->current() === '(') {
            $this->consume();
            $value = $this->parseExpression();

            if ($this->consume() !== ')') {
                throw new LogicException('The amount expression has unmatched parentheses.');
            }

            return $value;
        }

        $start = $this->position;

        while (ctype_digit($this->current())) {
            $this->position++;
        }

        if ($start === $this->position) {
            throw new LogicException('The amount expression is invalid.');
        }

        return (int) substr($this->expression, $start, $this->position - $start);
    }

    private function current(): string
    {
        return $this->expression[$this->position] ?? '';
    }

    private function consume(): string
    {
        return $this->expression[$this->position++] ?? '';
    }

    private function assertInteger(mixed $value): void
    {
        if (! is_int($value)) {
            throw new LogicException('The amount expression exceeds the supported range.');
        }
    }
}
