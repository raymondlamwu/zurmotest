<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2011 Zurmo Inc.
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
     * Sanitizer for attributes that are ids. This would be used if mapping an id for the model that is being imported.
     */
    class SelfIdValueTypeSanitizerUtil extends IdValueTypeSanitizerUtil
    {
        public static function supportsSqlAttributeValuesDataAnalysis()
        {
            return false;
        }

        public static function getBatchAttributeValueDataAnalyzerType()
        {
            return 'SelfIdValueType';
        }

        public static function getLinkedMappingRuleType()
        {
            return 'IdValueType';
        }

        public static function sanitizeValue($modelClassName, $attributeName, $value, $mappingRuleData)
        {
            assert('is_string($modelClassName)');
            assert('is_string($attributeName) && $attributeName == "id"');
            assert('$value != ""');
            assert('$mappingRuleData["type"] == IdValueTypeMappingRuleForm::ZURMO_MODEL_ID ||
                    $mappingRuleData["type"] == IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID');
            if($value == null)
            {
                return $value;
            }
            $model                   = new $modelClassName(false);
            $attributeModelClassName = $this->resolveAttributeModelClassName($model,$attributeName);
            if($mappingRuleData["type"] == IdValueTypeMappingRuleForm::ZURMO_MODEL_ID)
            {
                try
                {
                    $attributeModelClassName::getById((int)$value);
                    return (int)$value;
                }
                catch(NotFoundException $e)
                {
                    throw new InvalidValueToSanitizeException();
                }
            }
            elseif($mappingRuleData["type"] == IdValueTypeMappingRuleForm::EXTERNAL_SYSTEM_ID)
            {
                try
                {
                    $model = static::getModelByExternalSystemIdAndModelClassName($value, $attributeModelClassName);
                }
                catch(NotFoundException $e)
                {
                    throw new InvalidValueToSanitizeException();
                }
            }
        }
    }
?>