<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Properties\ModelHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionException;

/**
 * Types a model's getTable() as the table Eloquent resolves for the model
 * class, as read from its `$table` property, its `#[Table]` attribute or the
 * naming convention.
 *
 * Like the model property types, this assumes an instance uses the table of
 * the class it is typed as: a subclass with another table, or a table changed
 * at runtime with setTable(), is not seen.
 */
#[AutowiredService]
final class ModelGetTableDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getTable';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $classReflections = $scope->getType($methodCall->var)->getObjectClassReflections();

        if ($classReflections === []) {
            return null;
        }

        $tableTypes = [];

        foreach ($classReflections as $classReflection) {
            // An override can name its table any way it likes, so only Eloquent's own resolution is followed
            if (
                ! $classReflection->is(Model::class)
                || $classReflection->isAbstract()
                || $classReflection->getNativeMethod('getTable')->getDeclaringClass()->getName() !== Model::class
            ) {
                return null;
            }

            try {
                $tableTypes[] = new ConstantStringType(ModelHelper::newInstanceWithoutConstructor($classReflection)->getTable());
            } catch (ReflectionException) {
                return null;
            }
        }

        return TypeCombinator::union(...$tableTypes);
    }
}
