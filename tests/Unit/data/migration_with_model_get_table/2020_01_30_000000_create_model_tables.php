<?php

declare(strict_types=1);

namespace Tests\Unit\Data\MigrationWithModelGetTable;

use App\Importer;
use App\Mechanic;
use App\ModelWithDateColumnCasts;
use App\Post;
use App\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelTables extends Migration
{
    public function up(): void
    {
        Schema::create((new Mechanic())->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });

        Schema::create((new ModelWithDateColumnCasts())->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        $mechanic = new Mechanic();
        $model    = $mechanic;

        Schema::table($model->getTable(), static function (Blueprint $table) {
            $table->string('nickname')->nullable();
        });

        $model = new Post();

        Schema::create($model->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        $model = new Role();

        Schema::create($model->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create((new Importer())->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create($unassigned->getTable(), static function (Blueprint $table) {
            $table->bigIncrements('id');
        });
    }
}
