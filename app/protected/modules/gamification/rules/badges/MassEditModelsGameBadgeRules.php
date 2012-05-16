<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2012 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * Base class for defining the badge associated with mass editing models
     */
    abstract class MassEditModelsGameBadgeRules extends GameBadgeRules
    {
        public static function badgeGradeUserShouldHaveByPointsAndScoresByModelClassName(
                               $userPointsByType, $userScoresByType, $modelClassName)
        {
            assert('is_array($userPointsByType)');
            assert('is_array($userScoresByType)');
            assert('is_string($modelClassName)');
            $elementName = 'Search' . $modelClassName;
            if (isset($userScoresByType[$elementName]))
            {
                if ($userScoresByType[$elementName]->value < 1)
                {
                    return 0;
                }
                if ($userScoresByType[$elementName]->value < 2)
                {
                    return 1;
                }
                elseif ($userScoresByType[$elementName]->value < 6)
                {
                    return 2;
                }
                elseif ($userScoresByType[$elementName]->value < 11)
                {
                    return 3;
                }
                elseif ($userScoresByType[$elementName]->value < 21)
                {
                    return 4;
                }
                elseif ($userScoresByType[$elementName]->value < 31)
                {
                    return 5;
                }
                elseif ($userScoresByType[$elementName]->value < 41)
                {
                    return 6;
                }
                elseif ($userScoresByType[$elementName]->value < 51)
                {
                    return 7;
                }
                elseif ($userScoresByType[$elementName]->value < 61)
                {
                    return 8;
                }
                elseif ($userScoresByType[$elementName]->value < 71)
                {
                    return 9;
                }
                elseif ($userScoresByType[$elementName]->value < 81)
                {
                    return 10;
                }
                elseif ($userScoresByType[$elementName]->value < 91)
                {
                    return 11;
                }
                elseif ($userScoresByType[$elementName]->value < 101)
                {
                    return 12;
                }
                elseif ($userScoresByType[$elementName]->value >= 125)
                {
                    return 13;
                }
            }
            return 0;
        }
    }
?>