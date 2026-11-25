<?php

namespace ModelGetTableLaravel13;

use App\Member;

use function PHPStan\Testing\assertType;

function test(Member $member): void
{
    assertType("'users'", $member->getTable());
}
