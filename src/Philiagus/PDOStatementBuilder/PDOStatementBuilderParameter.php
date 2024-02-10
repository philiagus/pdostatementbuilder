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

interface PDOStatementBuilderParameter
{

    /**
     * Returns the representation of this value in PDO statements
     * Should return a scalar value, but that is subject to your
     * value transformation setting
     *
     * The by reference provided type can be filled with the desired
     * \PDO type constant or left empty for automatic inference.
     *
     * If provided the $type is filled with the type provided by
     * the parameter binding.
     *
     * @param int|null $type
     *
     * @return mixed
     */
    public function toPDOStatementValue(?int &$type = null);

}
