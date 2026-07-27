<?php

namespace Tests\Unit\Support;

use App\Support\ComplaintStatus;
use PHPUnit\Framework\TestCase;

class ComplaintStatusTest extends TestCase
{
    public function test_an_open_complaint_can_be_taken_in_charge_or_closed(): void
    {
        $this->assertSame(
            [ComplaintStatus::EN_COURS, ComplaintStatus::CLOTUREE],
            ComplaintStatus::allowedNextStatuses(ComplaintStatus::OUVERTE),
        );
    }

    public function test_a_resolved_complaint_can_only_be_closed(): void
    {
        $this->assertSame([ComplaintStatus::CLOTUREE], ComplaintStatus::allowedNextStatuses(ComplaintStatus::RESOLUE));
    }

    public function test_a_closed_complaint_is_a_dead_end(): void
    {
        $this->assertSame([], ComplaintStatus::allowedNextStatuses(ComplaintStatus::CLOTUREE));
        $this->assertTrue(ComplaintStatus::isTerminal(ComplaintStatus::CLOTUREE));
    }

    public function test_non_terminal_statuses(): void
    {
        $this->assertFalse(ComplaintStatus::isTerminal(ComplaintStatus::OUVERTE));
        $this->assertFalse(ComplaintStatus::isTerminal(ComplaintStatus::EN_COURS));
        $this->assertFalse(ComplaintStatus::isTerminal(ComplaintStatus::EN_ATTENTE_CLIENT));
        $this->assertFalse(ComplaintStatus::isTerminal(ComplaintStatus::RESOLUE));
    }
}
