<?php

namespace App\Services;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\TicketSequence;
use InvalidArgumentException;

class TicketCodeService
{
    public const SEQUENCE_LENGTH = 5;
    public const FIXED_LENGTH = 12;

    public function generate(Team $team, Category $category, IssueType $issueType, Priority $priority): string
    {
        $prefix = $this->prefix($team, $category, $issueType, $priority);

        return TicketSequence::nextCode($prefix, self::SEQUENCE_LENGTH);
    }

    public function prefix(Team $team, Category $category, IssueType $issueType, Priority $priority): string
    {
        return
            $this->padCode($team->code_num, 1, 'Team') .
            $this->padCode($category->code_num, 2, 'Category') .
            $this->padCode($issueType->code_num, 3, 'Issue Type') .
            $this->padCode($priority->code_num, 1, 'Priority');
    }

    public function isValidFixedCode(string $ticketCode): bool
    {
        return preg_match('/^\d{' . self::FIXED_LENGTH . '}$/', $ticketCode) === 1;
    }

    private function padCode(null|string|int $value, int $length, string $label): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            throw new InvalidArgumentException("{$label} code_num is required for ticket code generation.");
        }

        if (strlen($digits) > $length) {
            throw new InvalidArgumentException("{$label} code_num must not exceed {$length} digit(s).");
        }

        return str_pad($digits, $length, '0', STR_PAD_LEFT);
    }
}
