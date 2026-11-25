<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\StatementContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;

use function array_values;
use function is_string;
use function ksort;

/**
 * Types expressions in a migration method, such as the table a `Schema` call
 * names, the way PHPStan types them in analysed code.
 *
 * Only the assignments to the variables an expression reads are analysed. The
 * schema is still being read while this runs, so analysing the rest of the
 * method, such as a data migration reading model properties, would cache those
 * properties as missing.
 *
 * @internal
 */
#[AutowiredService]
final class MigrationScopeResolver
{
    public function __construct(
        private ScopeFactory $scopeFactory,
        private NodeScopeResolver $nodeScopeResolver,
    ) {
    }

    /**
     * The type of an expression as the method's statements leave it before the
     * statement holding it, or null when the expression is not in the method.
     */
    public function getType(string $file, string|null $namespace, Stmt\ClassMethod $method, Expr $expr): Type|null
    {
        $statements = array_values($method->stmts ?? []);
        $nodeFinder = new NodeFinder();
        $position   = null;

        foreach ($statements as $index => $statement) {
            if ($nodeFinder->findFirst($statement, static fn (Node $node): bool => $node === $expr) !== null) {
                $position = $index;
                break;
            }
        }

        if ($position === null) {
            return null;
        }

        $scope = $this->scopeFactory->create(ScopeContext::create($file));

        if ($namespace !== null) {
            $scope = $scope->enterNamespace($namespace);
        }

        $assignments = $this->assignmentsRead($expr, $statements, $position);

        if ($assignments !== []) {
            $scope = $this->nodeScopeResolver->processStmtNodes(
                $method,
                $assignments,
                $scope,
                static function (): void {
                },
                StatementContext::createTopLevel(),
            )->getScope();
        }

        return $scope->getType($expr);
    }

    /**
     * The top-level assignments before the position that the expression reads,
     * directly or through the variables those assignments read, in order.
     *
     * @param list<Stmt> $statements
     *
     * @return list<Stmt\Expression>
     */
    private function assignmentsRead(Expr $expr, array $statements, int $position): array
    {
        $variables   = $this->variablesRead($expr);
        $assignments = [];

        for ($index = $position - 1; $index >= 0 && $variables !== []; $index--) {
            $statement = $statements[$index];

            if (
                ! $statement instanceof Stmt\Expression
                || ! $statement->expr instanceof Expr\Assign
                || ! $statement->expr->var instanceof Expr\Variable
                || ! is_string($statement->expr->var->name)
                || ! isset($variables[$statement->expr->var->name])
            ) {
                continue;
            }

            // The nearest assignment is the one the expression reads
            unset($variables[$statement->expr->var->name]);
            $variables          += $this->variablesRead($statement->expr->expr);
            $assignments[$index] = $statement;
        }

        ksort($assignments);

        return array_values($assignments);
    }

    /** @return array<string, true> */
    private function variablesRead(Expr $expr): array
    {
        $variables = [];

        /** @var list<Expr\Variable> $nodes */
        $nodes = (new NodeFinder())->findInstanceOf($expr, Expr\Variable::class);

        foreach ($nodes as $variable) {
            if (! is_string($variable->name)) {
                continue;
            }

            $variables[$variable->name] = true;
        }

        return $variables;
    }
}
