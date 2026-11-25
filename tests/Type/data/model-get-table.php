<?php

namespace ModelGetTable;

use App\Mechanic;
use App\ModelWithDateColumnCasts;
use App\User;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

class ModelWithGetTableOverride extends Model
{
    public function getTable(): string
    {
        return 'tenant_' . parent::getTable();
    }
}

function test(Mechanic $mechanic, Mechanic|ModelWithDateColumnCasts $either, Model $model, ModelWithGetTableOverride $override, Mechanic|null $maybe): void
{
    assertType("'mechanics'", (new Mechanic())->getTable());
    assertType("'mechanics'", $mechanic->getTable());
    assertType("'users'", (new User())->getTable());
    assertType("'model_with_casts'", (new ModelWithDateColumnCasts())->getTable());
    assertType("'mechanics'|'model_with_casts'", $either->getTable());
    assertType("'mechanics'|null", $maybe?->getTable());
    assertType('string', $model->getTable());
    assertType('string', $override->getTable());
}
