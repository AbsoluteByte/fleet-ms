<?php

namespace Tests\Unit;

use App\Support\PhvlWorkflow;
use PHPUnit\Framework\TestCase;

class PhvlWorkflowTest extends TestCase
{
    public function test_appointment_at_uses_green_class_when_value_is_set(): void
    {
        $class = PhvlWorkflow::statusBtnClass(
            PhvlWorkflow::FIELD_APPOINTMENT_AT,
            '',
            '2026-07-01T10:00'
        );

        $this->assertSame('btn-outline-success', $class);
    }

    public function test_appointment_at_uses_neutral_class_when_value_is_empty(): void
    {
        $class = PhvlWorkflow::statusBtnClass(
            PhvlWorkflow::FIELD_APPOINTMENT_AT,
            '',
            ''
        );

        $this->assertSame('btn-outline-secondary', $class);
    }
}
