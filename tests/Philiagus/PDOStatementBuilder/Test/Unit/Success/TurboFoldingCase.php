<?php
/**
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Bittner <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder\Test\Unit\Success;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Statement;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class TurboFoldingCase extends SuccessUnit
{
    private $expected = null;

    public function subCases(): array
    {
        $cases = [];
        foreach ([true, false] as $ifValue) {
            foreach ([true, false] as $elseIfValue) {
                foreach ([true, false] as $if_ifValue) {
                    foreach ([true, false] as $if_elseIfValue) {
                        foreach ([true, false] as $elseIf_ifValue) {
                            foreach ([true, false] as $elseIf_elseIfValue) {
                                foreach ([true, false] as $else_ifValue) {
                                    foreach ([true, false] as $else_elseIfValue) {
                                        $caseName = ($ifValue ? 'yes' : 'no') . ' / ' .
                                            ($elseIfValue ? 'yes' : 'no') . ' / ' .
                                            ($if_ifValue ? 'yes' : 'no') . ' / ' .
                                            ($if_elseIfValue ? 'yes' : 'no') . ' / ' .
                                            ($elseIf_ifValue ? 'yes' : 'no') . ' / ' .
                                            ($elseIf_elseIfValue ? 'yes' : 'no') . ' / ' .
                                            ($else_ifValue ? 'yes' : 'no') . ' / ' .
                                            ($else_elseIfValue ? 'yes' : 'no');

                                        $value = '';
                                        if ($ifValue) {
                                            $value .= '1.';
                                            if ($if_ifValue) {
                                                $value .= '1';
                                            } elseif ($if_elseIfValue) {
                                                $value .= '2';
                                            } else {
                                                $value .= '3';
                                            }
                                        } elseif ($elseIfValue) {
                                            $value .= '2.';
                                            if ($elseIf_ifValue) {
                                                $value .= '4';
                                            } elseif ($elseIf_elseIfValue) {
                                                $value .= '5';
                                            } else {
                                                $value .= '6';
                                            }
                                        } else {
                                            $value .= '3.';
                                            if ($else_ifValue) {
                                                $value .= '7';
                                            } elseif ($else_elseIfValue) {
                                                $value .= '8';
                                            } else {
                                                $value .= '9';
                                            }
                                        }
                                        $cases[$caseName] = [
                                            $ifValue,
                                            $elseIfValue,
                                            $if_ifValue,
                                            $if_elseIfValue,
                                            $elseIf_ifValue,
                                            $elseIf_elseIfValue,
                                            $else_ifValue,
                                            $else_elseIfValue,
                                            $value,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $cases;
    }

    protected
    function buildStatement(Builder $builder, array $further): Statement
    {
        [
            $if,
            $elseIf,
            $if_if,
            $if_elseIf,
            $elseIf_if,
            $elseIf_elseIf,
            $else_if,
            $else_elseIf,
            $this->expected,
        ] = $further;

        return $builder->build(
            "A{$builder->if($if)}" .
            "1.{$builder->if($if_if)}1{$builder->elseif($if_elseIf)}2{$builder->else()}3{$builder->endif()}" .
            "{$builder->elseif($elseIf)}" .
            "2.{$builder->if($elseIf_if)}4{$builder->elseif($elseIf_elseIf)}5{$builder->else()}6{$builder->endif()}" .
            "{$builder->else()}" .
            "3.{$builder->if($else_if)}7{$builder->elseif($else_elseIf)}8{$builder->else()}9{$builder->endif()}" .
            "{$builder->endif()}X"
        );
    }

    protected
    function getStatement(): string
    {
        return 'A' . $this->expected . 'X';
    }
}