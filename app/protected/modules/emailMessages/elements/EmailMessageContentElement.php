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
     * Display email message content.
     */
    class EmailMessageContentElement extends Element
    {
        protected function renderControlNonEditable()
        {
            assert('$this->model->{$this->attribute} instanceof EmailMessageContent');
            $emailMessageContent = $this->model->{$this->attribute};
            if ($emailMessageContent->htmlContent != null)
            {
                return Yii::app()->format->html($emailMessageContent->htmlContent);
            }
            elseif ($emailMessageContent->textContent != null)
            {
                return Yii::app()->format->text($emailMessageContent->textContent);
            }
        }

        protected function renderControlEditable()
        {
            $emailMessageContent     = $this->model->{$this->attribute};
            $inputNameIdPrefix       = $this->attribute;
            $attribute               = 'htmlContent';
            $id                      = $this->getEditableInputId  ($inputNameIdPrefix, $attribute);
            $htmlOptions             = array();
            $htmlOptions['id']       = $id;
            $htmlOptions['name']     = $this->getEditableInputName($inputNameIdPrefix, $attribute);
            $cClipWidget   = new CClipWidget();
            $cClipWidget->beginClip("Redactor");
            $cClipWidget->widget('application.core.widgets.Redactor', array(
                                        'htmlOptions' => $htmlOptions,
                                        'content'     => $emailMessageContent->$attribute,
                                        'buttons'     => "['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|',
                                                           'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                                                           'fontcolor', 'backcolor', '|',
                                                           'alignleft', 'aligncenter', 'alignright', 'justify', '|',
                                                           'horizontalrule']",
            ));
            $cClipWidget->endClip();
            $content  = $cClipWidget->getController()->clips['Redactor'];
            $content .= $this->form->error($emailMessageContent, $attribute);
            return $content;
        }

        protected function renderLabel()
        {
            $label = Yii::t('Default', 'Body');
            if ($this->form === null)
            {
                return $label;
            }
            else
            {
                return $this->form->labelEx($this->model,
                                            $this->attribute,
                                            array('for' => $this->getEditableInputId($this->attribute, 'htmlContent'),
                                                  'label' => $label));
            }
        }
    }
?>