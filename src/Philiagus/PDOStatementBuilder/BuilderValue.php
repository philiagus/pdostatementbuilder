<?php
/*
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Eicher <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder;

interface BuilderValue
{

    /**
     * Called to resolve the BuilderValue before trying to use it in the context of a builder function
     * @return mixed
     */
    public function resolveAsPDOStatementBuilderValue(): mixed;

}
