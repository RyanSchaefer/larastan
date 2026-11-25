<?php

declare(strict_types=1);

namespace Tests\Unit\Data\MigrationWithModelGetTableAttribute;

use App\Member;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    public function up(): void
    {
        Schema::create((new Member())->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
        });
    }
}
