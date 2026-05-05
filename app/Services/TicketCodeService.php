<?php

namespace App\Services;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\TicketSequence;

class TicketCodeService
{
    public function generate(Team $team, Category $category, IssueType $issueType, Priority $priority): string
    {
        $prefix =
            $this->padCode($team->code_num, 1) .
            $this->padCode($category->code_num, 2) .
            $this->padCode($issueType->code_num, 3) .
            $this->padCode($priority->code_num, 1);

        return TicketSequence::nextCode($prefix);
    }

    private function padCode(?string $value, int $length): string
    {
        return str_pad((string) $value, $length, '0', STR_PAD_LEFT);
    }
}
