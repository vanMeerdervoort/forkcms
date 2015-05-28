<?php

namespace Backend\Core\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;

use Backend\Core\Engine\Model as BackendModel;

/**
 * This is our extended version of \SpoonForm
 *
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 */
class Form extends \SpoonForm
{
    /**
     * The header instance
     *
     * @var    Header
     */
    private $header;

    /**
     * The URL instance
     *
     * @var    Url
     */
    private $URL;

    /**
     * Show the global error
     *
     * @var    bool
     */
    private $useGlobalError = true;

    /**
     * @param string $name           Name of the form.
     * @param string $action         The action (URL) whereto the form will be submitted, if not provided it
     *                               will be autogenerated.
     * @param string $method         The method to use when submitting the form, default is POST.
     * @param bool   $useToken       Should we automagically add a formtoken?
     * @param bool   $useGlobalError Should we automagically show a global error?
     */
    public function __construct(
        $name = null,
        $action = null,
        $method = 'post',
        $useToken = true,
        $useGlobalError = true
    ) {
        if (BackendModel::getContainer()->has('url')) {
            $this->URL = BackendModel::getContainer()->get('url');
        }
        if (BackendModel::getContainer()->has('header')) {
            $this->header = BackendModel::getContainer()->get('header');
        }
        $this->useGlobalError = (bool) $useGlobalError;

        // build a name if there wasn't one provided
        $name = ($name === null) ? \SpoonFilter::toCamelCase(
            $this->URL->getModule() . '_' . $this->URL->getAction(),
            '_',
            true
        ) : (string) $name;

        // build the action if it wasn't provided
        $action = ($action === null) ? '/' . $this->URL->getQueryString() : (string) $action;

        // call the real form-class
        parent::__construct($name, $action, $method, $useToken);

        // add default classes
        $this->setParameter('id', $name);
        $this->setParameter('class', 'fork-form submitWithLink');
    }

    /**
     * Adds a button to the form
     *
     * @param string $name  Name of the button.
     * @param string $value The value (or label) that will be printed.
     * @param string $type  The type of the button (submit is default).
     * @param string $class Class(es) that will be applied on the button.
     * @return \SpoonFormButton
     */
    public function addButton($name, $value, $type = 'submit', $class = null)
    {
        $name = (string) $name;
        $value = (string) $value;
        $type = (string) $type;
        $class = ($class !== null) ? (string) $class : 'btn btn-primary';

        // do a check
        if ($type == 'submit' && $name == 'submit') {
            throw new Exception(
                'You can\'t add buttons with the name submit. JS freaks out when we
                replace the buttons with a link and use that link to submit the form.'
            );
        }

        // create and return a button
        return parent::addButton($name, $value, $type, $class);
    }

    /**
     * Adds a single checkbox.
     *
     * @param string $name       The name of the element.
     * @param bool   $checked    Should the checkbox be checked?
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormCheckbox
     */
    public function addCheckbox($name, $checked = false, $class = null, $classError = null)
    {
        $name = (string) $name;
        $checked = (bool) $checked;
        $class = ($class !== null) ? (string) $class : 'fork-form-checkbox';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // create and return a checkbox
        return parent::addCheckbox($name, $checked, $class, $classError);
    }

    /**
     * Adds a datefield to the form
     *
     * @param string $name       Name of the element.
     * @param mixed  $value      The value for the element.
     * @param string $type       The type (from, till, range) of the datepicker.
     * @param int    $date       The date to use.
     * @param int    $date2      The second date for a rangepicker.
     * @param string $class      Class(es) that have to be applied on the element.
     * @param string $classError Class(es) that have to be applied when an error occurs on the element.
     * @return FormDate
     * @throws Exception
     * @throws \SpoonFormException
     */
    public function addDate(
        $name,
        $value = null,
        $type = null,
        $date = null,
        $date2 = null,
        $class = null,
        $classError = null
    ) {
        $name = (string) $name;
        $value = ($value !== null) ? (($value !== '') ? (int) $value : '') : null;
        $type = \SpoonFilter::getValue($type, array('from', 'till', 'range'), 'none');
        $date = ($date !== null) ? (int) $date : null;
        $date2 = ($date2 !== null) ? (int) $date2 : null;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-date inputDate';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // validate
        if ($type == 'from' && ($date == 0 || $date == null)) {
            throw new Exception('A datefield with type "from" should have a valid date-parameter.');
        }
        if ($type == 'till' && ($date == 0 || $date == null)) {
            throw new Exception('A datefield with type "till" should have a valid date-parameter.');
        }
        if ($type == 'range' && ($date == 0 || $date2 == 0 || $date == null || $date2 == null)) {
            throw new Exception('A datefield with type "range" should have 2 valid date-parameters.');
        }

        // @later	get preferred mask & first day
        $mask = 'd/m/Y';
        $firstday = 1;

        // build attributes
        $attributes['data-mask'] = str_replace(
            array('d', 'm', 'Y', 'j', 'n'),
            array('dd', 'mm', 'yy', 'd', 'm'),
            $mask
        );
        $attributes['data-firstday'] = $firstday;

        // add extra classes based on type
        switch ($type) {
            // start date
            case 'from':
                $class .= ' fork-form-date-from inputDatefieldFrom';
                $classError .= ' inputDatefieldFrom';
                $attributes['data-startdate'] = date('Y-m-d', $date);
                break;

            // end date
            case 'till':
                $class .= ' fork-form-date-till inputDatefieldTill';
                $classError .= ' inputDatefieldTill';
                $attributes['data-enddate'] = date('Y-m-d', $date);
                break;

            // date range
            case 'range':
                $class .= ' fork-form-date-range inputDatefieldRange';
                $classError .= ' inputDatefieldRange';
                $attributes['data-startdate'] = date('Y-m-d', $date);
                $attributes['data-enddate'] = date('Y-m-d', $date2);
                break;

            // normal date field
            default:
                $class .= ' inputDatefieldNormal';
                $classError .= ' inputDatefieldNormal';
                break;
        }

        // create a datefield
        $this->add(new FormDate($name, $value, $mask, $class, $classError));

        // set attributes
        parent::getField($name)->setAttributes($attributes);

        // return datefield
        return parent::getField($name);
    }

    /**
     * Adds a single dropdown.
     *
     * @param string $name              Name of the element.
     * @param array  $values            Values for the dropdown.
     * @param string $selected          The selected elements.
     * @param bool   $multipleSelection Is it possible to select multiple items?
     * @param string $class             Class(es) that will be applied on the element.
     * @param string $classError        Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormDropdown
     */
    public function addDropdown(
        $name,
        array $values = null,
        $selected = null,
        $multipleSelection = false,
        $class = null,
        $classError = null
    ) {
        $name = (string) $name;
        $values = (array) $values;
        $selected = ($selected !== null) ? $selected : null;
        $multipleSelection = (bool) $multipleSelection;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-select';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // special classes for multiple
        if ($multipleSelection) {
            $class .= ' selectMultiple';
            $classError .= ' selectMultipleError';
        }

        // create and return a dropdown
        return parent::addDropdown($name, $values, $selected, $multipleSelection, $class, $classError);
    }

    /**
     * Add an editor field
     *
     * @param string $name       The name of the element.
     * @param string $value      The value inside the element.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @param bool   $HTML       Will the field contain HTML?
     * @return \SpoonFormTextarea
     */
    public function addEditor($name, $value = null, $class = null, $classError = null, $HTML = true)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $class = 'inputEditor ' . (string) $class;
        $classError = 'inputEditorError ' . (string) $classError;
        $HTML = (bool) $HTML;

        // we add JS because we need CKEditor
        $this->header->addJS('ckeditor/ckeditor.js', 'Core', false);
        $this->header->addJS('ckeditor/adapters/jquery.js', 'Core', false);
        $this->header->addJS('ckfinder/ckfinder.js', 'Core', false);

        // add the internal link lists-file
        if (is_file(FRONTEND_CACHE_PATH . '/Navigation/editor_link_list_' . Language::getWorkingLanguage() . '.js')) {
            $timestamp = @filemtime(
                FRONTEND_CACHE_PATH . '/Navigation/editor_link_list_' . Language::getWorkingLanguage() . '.js'
            );
            $this->header->addJS(
                '/src/Frontend/Cache/Navigation/editor_link_list_' . Language::getWorkingLanguage(
                ) . '.js?m=' . $timestamp,
                null,
                false,
                true,
                false
            );
        }

        // create and return a textarea for the editor
        return $this->addTextArea($name, $value, $class, $classError, $HTML);
    }

    /**
     * Adds a single file field.
     *
     * @param string $name       Name of the element.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormFile
     */
    public function addFile($name, $class = null, $classError = null)
    {
        $name = (string) $name;
        $class = ($class !== null) ? (string) $class : 'fork-form-file';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // add element
        $this->add(new FormFile($name, $class, $classError));

        return $this->getField($name);
    }

    /**
     * Adds a single image field.
     *
     * @param string $name       The name of the element.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormImage
     */
    public function addImage($name, $class = null, $classError = null)
    {
        $name = (string) $name;
        $class = ($class !== null) ? (string) $class : 'fork-form-image';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // add element
        $this->add(new FormImage($name, $class, $classError));

        return $this->getField($name);
    }

    /**
     * Adds a multiple checkbox.
     *
     * @param string $name       The name of the element.
     * @param array  $values     The values for the checkboxes.
     * @param mixed  $checked    Should the checkboxes be checked?
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormMultiCheckbox
     */
    public function addMultiCheckbox($name, array $values, $checked = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $values = (array) $values;
        $checked = ($checked !== null) ? (array) $checked : null;
        $class = ($class !== null) ? (string) $class : 'fork-form-multi-checkbox';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // create and return a multi checkbox
        return parent::addMultiCheckbox($name, $values, $checked, $class, $classError);
    }

    /**
     * Adds a single password field.
     *
     * @param string $name       The name of the field.
     * @param string $value      The value for the field.
     * @param int    $maxLength  The maximum length for the field.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @param bool   $HTML       Will the field contain HTML?
     * @return \SpoonFormPassword
     */
    public function addPassword(
        $name,
        $value = null,
        $maxLength = null,
        $class = null,
        $classError = null,
        $HTML = false
    ) {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $maxLength = ($maxLength !== null) ? (int) $maxLength : null;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-password inputPassword';
        $classError = ($classError !== null) ? (string) $classError : 'error';
        $HTML = (bool) $HTML;

        // create and return a password field
        return parent::addPassword($name, $value, $maxLength, $class, $classError, $HTML);
    }

    /**
     * Adds a single radiobutton.
     *
     * @param string $name       The name of the element.
     * @param array  $values     The possible values for the radiobutton.
     * @param string $checked    Should the element be checked?
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormRadiobutton
     */
    public function addRadiobutton($name, array $values, $checked = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $values = (array) $values;
        $checked = ($checked !== null) ? (string) $checked : null;
        $class = ($class !== null) ? (string) $class : 'fork-form-radio';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // create and return a radio button
        return parent::addRadiobutton($name, $values, $checked, $class, $classError);
    }

    /**
     * Adds a single textfield.
     *
     * @param string $name       The name of the element.
     * @param string $value      The value inside the element.
     * @param int    $maxLength  The maximum length for the value.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @param bool   $HTML       Will this element contain HTML?
     * @return \SpoonFormText
     */
    public function addText($name, $value = null, $maxLength = 255, $class = null, $classError = null, $HTML = false)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $maxLength = ($maxLength !== null) ? (int) $maxLength : null;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-text';
        $classError = ($classError !== null) ? (string) $classError : 'error';
        $HTML = (bool) $HTML;

        // create and return a textfield
        return parent::addText($name, $value, $maxLength, $class, $classError, $HTML);
    }

    /**
     * Adds a single textarea.
     *
     * @param string $name       The name of the element.
     * @param string $value      The value inside the element.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @param bool   $HTML       Will the element contain HTML?
     * @return \SpoonFormTextarea
     */
    public function addTextarea($name, $value = null, $class = null, $classError = null, $HTML = false)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-textarea';
        $classError = ($classError !== null) ? (string) $classError : 'error';
        $HTML = (bool) $HTML;

        // create and return a textarea
        return parent::addTextarea($name, $value, $class, $classError, $HTML);
    }

    /**
     * Adds a single timefield.
     *
     * @param string $name       The name of the element.
     * @param string $value      The value inside the element.
     * @param string $class      Class(es) that will be applied on the element.
     * @param string $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormTime
     */
    public function addTime($name, $value = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $class = ($class !== null) ? (string) $class : 'form-control fork-form-time inputTime';
        $classError = ($classError !== null) ? (string) $classError : 'error';

        // create and return a timefield
        return parent::addTime($name, $value, $class, $classError);
    }

    /**
     * Fetches all the values for this form as key/value pairs
     *
     * @param mixed $excluded Which elements should be excluded?
     * @return array
     */
    public function getValues($excluded = array('form', 'save', 'form_token', '_utf8'))
    {
        return parent::getValues($excluded);
    }

    /**
     * Checks to see if this form has been correctly submitted. Will revalidate by default.
     *
     * @param bool $revalidate Do we need to enforce validation again, even if it might already been done before?
     * @return bool
     */
    public function isCorrect($revalidate = true)
    {
        return parent::isCorrect($revalidate);
    }

    /**
     * Parse the form
     *
     * @param \SpoonTemplate $tpl The template instance wherein the form will be parsed.
     */
    public function parse($tpl)
    {
        parent::parse($tpl);
        $this->validate();

        // if the form is submitted but there was an error, assign a general error
        if ($this->useGlobalError && $this->isSubmitted() && !$this->isCorrect()) {
            $tpl->assign('formError', true);
        }
    }
}
