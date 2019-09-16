<?php
namespace Spindogs\Laravel\Form;

use HTMLPurifier_Config;
use HTMLPurifier;
use DateTime;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class Form
{
    protected static $_lang;

    protected $handle;
    protected $action;
    protected $method = 'post';
    protected $fields = [];
    protected $errors = [];
    protected $uniqids = [];
    protected $scripts = [];
    protected $is_multipart = false;
    protected $lang;
    protected $translate_errors = true;

    public $error_class = '__error';
    public $error_wrap = 'formerrors';
    public $field_wrap = 'formfield';
    public $label_wrap = 'formfield-label';
    public $input_wrap = 'formfield-input';
    public $required_wrap = 'formfield-required';
    public $sublabel_wrap = 'formfield-sublabel';
    public $helper_wrap = 'formfield-helper';
    public $submit_wrap = 'formsubmit';
    public $require_all = true;
    public $disable_all = false;
    public $placeholder_all = false;
    public $thumbnail_class = 'formfield-thumbnail';
    public $thumbnail_remove = 'formfield-remove';
    public $placeholders = [];
    public $labels = [];

    public function __construct($handle = null)
    {
        $this->handle = $handle;

        if (self::$_lang) {
            $lang = self::$_lang;
            $this->lang($lang);
        }

        if (session()->has('errors')) {
            if ($handle) {
                if (session('errors')->hasBag($handle)) {
                    $message_bag = session('errors')->getBag($handle);
                } else {
                    $message_bag = null;
                }
            } else {
                $message_bag = session('errors')->getBag('default');
            }

            if ($message_bag && !$message_bag->isEmpty()) {
                $this->errors = $message_bag;
            }
        }
    }

    public function __invoke()
    {
        return $this->putForm();
    }

    /**
     * @param string $url
     * @return void
     */
    public function action($url)
    {
        $this->action = $url;
    }

    /**
     * @param string $lang
     * @return void
     */
    public function lang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * @param string $name
     * @param array $params
     * @return void
     */
    protected function add($name, $params)
    {
        $defaults = [
            'type' => '',
            'label' => '',
            'options' => [],
            'default' => '',
            'required' => null,
            'attrs' => []
        ];

        $params = array_merge($defaults, $params);

        if (isset($params['name'])) {
            unset($params['name']);
        }

        $this->fields[$name] = $params;
    }

    /**
     * @param string $name
     * @param string $default
     * @return void
     */
    public function hidden($name, $default)
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function text($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function email($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function number($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function password($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function file($name, $label = '', $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->is_multipart = true;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function textarea($name, $label, $default = null, $required = null, $attrs = [])
    {
        $attrs['rows'] = $attrs['rows'] ?? 4;
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function wysiwyg($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function select($name, $label, $options, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function radiobuttons($name, $label, $options, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param array $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function checkboxes($name, $label, $options, $default=[], $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param array $attrs
     * @return void
     */
    public function checkbox($name, $label, $default = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $label
     * @param array $attrs
     * @return void
     */
    public function submit($label, $attrs = [])
    {
        $name= 'submit';
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param DateTime|int $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function dateSelect($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param DateTime $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function datePicker($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param DateTime|string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function timeSelect($name, $label, $default = null, $required = null, $attrs = [])
    {
        $params = get_defined_vars();
        $params['type'] = __FUNCTION__;
        $this->add($name, $params);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function dobSelect($name, $label, $default = null, $required = null, $attrs = [])
    {
        $attrs['year_start'] = date('Y');
        $attrs['year_end'] = 1920;

        $this->dateSelect($name, $label, $default, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function dobPicker($name, $label, $default = null, $required = null, $attrs = [])
    {
        $attrs['min'] = mktime(0, 0, 0, 1, 1, 1920);
        $attrs['max'] = 'today';
        $attrs['num_years'] = 999;

        $this->datePicker($name, $label, $default, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function yesno($name, $label, $default = null, $required = null, $attrs = [])
    {
        if ($default !== null) {
            $default = intval($default);
        }

        $options = [
            1 => __('Yes'),
            0 => __('No')
        ];

        $this->radiobuttons($name, $label, $options, $default, $required, $attrs);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function gender($name, $label, $default = null, $required = null, $attrs = [])
    {
        $options = [
            'MALE' => __('Male'),
            'FEMALE' => __('Female')
        ];

        if (empty($attrs['use_radio'])) {
            $this->select($name, $label, $options, $default, $required, $attrs);
        } else {
            $this->radiobuttons($name, $label, $options, $default, $required, $attrs);
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $default
     * @param bool $required
     * @param array $attrs
     * @return void
     */
    public function salutation($name, $label, $default = null, $required = null, $attrs = [])
    {
        $options = [
            'Mr' => __('Mr'),
            'Mrs' => __('Mrs'),
            'Miss' => __('Miss'),
            'Ms' => __('Ms')
        ];

        $this->select($name, $label, $options, $default, $required, $attrs);
    }

    /**
     * @param string $name
     * @return object
     */
    public function getField($name)
    {
        if (isset($this->fields[$name])) {
            return (object)$this->fields[$name];
        } else {
            return;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public function getLabel($name)
    {
        if (isset($this->labels[$name])) {
            return $this->labels[$name];
        } else {
            return $this->getField($name)->label;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public function errorClass($name)
    {
        if ($this->errors && $this->errors->has($name)) {
            return self::escHtml($this->error_class);
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public function uniqId($name)
    {
        if (empty($this->uniqids[$name])) {
            $this->resetUniqid($name);
        }

        return $this->uniqids[$name];
    }

    /**
     * @param string $name
     * @return void
     */
    public function resetUniqid($name)
    {
        $this->uniqids[$name] = uniqid($this->handle);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRequired($name)
    {
        $field = $this->getField($name);

        if ($field->required === null && $this->require_all == false) {
            return false;
        } elseif ($field->required === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function open()
    {
        $html = '';
        $html .= '<form ';
        $html .= ($this->action ? 'action="'.self::escHtml($this->action).'" ' : '');
        $html .= ($this->method ? 'method="'.self::escHtml($this->method).'" ' : '');
        $html .= ($this->is_multipart ? 'enctype="multipart/form-data" ' : '');
        $html .= 'class="formwrap" ';
        $html .= '>';
        $html .= "\n";
        $html .= $this->csrf();
        return new HtmlString($html);
    }

    /**
     * @return string
     */
    public function close()
    {
        $html = '';
        $html .= '</form>';
        $html .= "\n";
        return new HtmlString($html);
    }

    /**
     * @return string
     */
    public function csrf()
    {
        $html = csrf_field()."\n";
        return new HtmlString($html);
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return (bool)$this->errors;
    }

    /**
     * @return string
     */
    public function errors()
    {
        if (!$this->errors) {
            return;
        }

        $html = '';
        $html .= '<div class="formerrors">'."\n";
        $html .= '<ul>'."\n";

        foreach ($this->errors->all() as $message) {
            $html .= '<li>'.self::escHtml($message).'</li>'."\n";
        }

        $html .= '</ul>'."\n";
        $html .= '</div>'."\n";

        return new HtmlString($html);
    }

    /**
     * @param string $name
     * @return string
     */
    public function label($name)
    {
        $html = '<label for="'.$this->uniqId($name).'" class="'.$this->errorClass($name).'">';
        $html .= $this->getLabel($name);
        $html .= $this->asterisk($name);
        $html .= '</label>'."\n";

        return new HtmlString($html);
    }

    /**
     * @param string $name
     * @return string
     */
    public function asterisk($name)
    {
        if (!$this->isRequired($name)) {
            return;
        }

        $html = '<span class="'.$this->required_wrap.'">';
        $html .= '*';
        $html .= '</span>';

        return new HtmlString($html);
    }

    /**
     * @param string $name
     * @return string
     */
    public function input($name)
    {
        $field = $this->getField($name);
        $value = old($name, $field->default);

        switch ($field->type) {
            case 'hidden':
            case 'text':
            case 'email':
            case 'number':
            case 'password':
                $html = $this->makeInput($name, $value);
            break;
            case 'file':
                $html = $this->makeFile($name, $value);
            break;
            case 'textarea':
                $html = $this->makeTextarea($name, $value);
            break;
            case 'wysiwyg':
            $html = $this->makeWysiwyg($name, $value);
            break;
            case 'select':
                $html = $this->makeSelect($name, $value);
            break;
            case 'radiobuttons':
                $html = $this->makeRadiobuttons($name, $value);
            break;
            case 'checkboxes':
                $html = '';
            break;
            case 'checkbox':
                $html = $this->makeCheckbox($name, $value);
            break;
            case 'submit':
                $html = '';
                $html .= '<button ';
                $html .= 'type="submit" ';
                $html .= $this->makeAttr('class', $name);
                $html .= '>';
                $html .= $field->label;
                $html .= '</button>';
            break;
            case 'dateSelect':
                $html = '';
            break;
            case 'datePicker':
                $html = $this->makeDatePicker($name, $value);
            break;
            case 'timeSelect':
                $html = '';
            break;
        }

        return new HtmlString($html);
    }

    /**
     * @param string $name
     * @return string
     */
    public function render($name = null)
    {
        if (!$name) {
            return $this->putForm();
        }

        $field = $this->getField($name);

        if (!$field) {
            return;
        }

        $modifier = '__'.strtolower($field->type);

        $this->uniqids[$name] = uniqid($this->handle);

        if ($field->type == 'hidden') {
            $html = '';
            $html .= $this->input($name);
        } elseif ($field->type == 'submit') {
            $html = '';
            $html .= '<div class="'.self::escHtml($this->submit_wrap).'">'."\n";
            $html .= $this->input($name);
            $html .= '</div>'."\n";
        } else {
            $html = '';
            $html .= '<div class="'.self::escHtml($this->field_wrap.' '.$modifier).'">'."\n";
            $html .= '<div class="'.self::escHtml($this->label_wrap).'">';
            $html .= $this->label($name);
            $html .= '</div>'."\n";
            $html .= '<div class="'.self::escHtml($this->input_wrap).'">';
            $html .= $this->input($name);
            $html .= '</div>';
            $html .= '</div>'."\n";
        }

        return new HtmlString($html);
    }

    /**
     * @return string
     */
    public function scripts()
    {
        $html = '';

        foreach ($this->scripts as $script_html) {
            $html .= $script_html;
            $html .= "\n";
        }

        return new HtmlString($html);
    }

    /**
     * @return string
     */
    protected function putForm()
    {
        $html = '';
        $html .= $this->errors();
        $html .= $this->open();

        foreach ($this->fields as $name => $x) {
            $html .= $this->render($name);
        }

        $html .= $this->close();

        return new HtmlString($html);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function makeName($name)
    {
        if (strpos($name, '.') === false) {
            return $name;
        }

        $parts = explode('.', $name);
        $name = array_shift($parts);

        foreach ($parts as $p) {
            $name .= '['.$p.']';
        }

        return $name;
    }

    /**
     * @param string $key
     * @param string $name
     * @param mixed $default
     * @return string
     */
    protected function makeAttr($key, $name, $default = null)
    {
        $field = $this->getField($name);
        $attrs = $field->attrs;

        if ($key == 'placeholder') {
            if (!isset($attrs[$key])) {
                $attrs[$key] = $this->placeholder_all;
            }

            if ($attrs[$key] === true) {
                $attrs[$key] = $field->label;
            } elseif (!$attrs[$key]) {
                unset($attrs[$key]);
            }
        }

        if ($key == 'lang') {
            if (isset($attrs[$key])) {
                //ignore if set directly
            } elseif ($this->lang) {
                $attrs[$key] = $this->lang;
            }
        }

        if (isset($attrs[$key])) {
            return ' '.$key.'="'.self::escHtml($attrs[$key]).'"';
        } elseif ($default) {
            return ' '.$key.'="'.self::escHtml($default).'"';
        } else {
            return '';
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeInput($name, $value)
    {
        $field = $this->getField($name);

        $html = '<input ';
        $html .= 'type="'.self::escHtml($field->type).'" ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'value="'.self::escHtml($value).'" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= $this->makeAttr('placeholder', $name);
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);
        $html .= $this->makeAttr('lang', $name);
        $html .= $this->makeAttr('autocomplete', $name);
        $html .= $this->makeAttr('step', $name);
        $html .= $this->makeAttr('min', $name);
        $html .= $this->makeAttr('max', $name);
        $html .= '>';

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeFile($name, $value)
    {
        $field = $this->getField($name);

        if (!empty($field->default) && !empty($field->attrs['disk']) && !empty($field->attrs['path_to'])) {
            $path_to = rtrim($field->attrs['path_to'], '/');
            $filename = ltrim($field->default, '/');
            $preview = Storage::disk($field->attrs['disk'])->url($path_to.'/'.$filename);
        } else {
            $preview = null;
        }

        $html = '';

        if ($preview) {
            $html .= '<img ';
            $html .= 'src="'.self::escHtml($preview).'" ';
            $html .= 'class="'.self::escHtml($this->thumbnail_class).'" ';
            $html .= 'id="'.$this->uniqId($name).'_PREVIEW" ';
            $html .= 'alt=""';
            $html .= 'style="';
            $html .= self::escHtml(!empty($field->attrs['width']) ? 'max-width:'.$field->attrs['width'].'px;' : null);
            $html .= self::escHtml(!empty($field->attrs['height']) ? 'max-height:'.$field->attrs['height'].'px;' : null);
            $html .= '" ';
            $html .= '>';
        }

        $html .= '<input ';
        $html .= 'type="'.self::escHtml($field->type).'" ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'value="'.self::escHtml($value).'" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= $this->makeAttr('placeholder', $name);
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);
        $html .= $this->makeAttr('autocomplete', $name);
        $html .= $this->makeAttr('step', $name);
        $html .= $this->makeAttr('min', $name);
        $html .= $this->makeAttr('max', $name);
        $html .= '>';

        if ($preview) {
            $html .= '<input ';
            $html .= 'type="hidden" ';
            $html .= 'name="'.$this->makeName($name).'_DELETE" ';
            $html .= 'id="'.$this->uniqId($name).'_DELETE" ';
            $html .= '>';
            $html .= '<a ';
            $html .= 'href="#" ';
            $html .= 'class="'.self::escHtml($this->thumbnail_remove).'" ';
            $html .= 'id="'.$this->uniqId($name).'_REMOVE" ';
            $html .= '>';
            $html .= '</a>';
        }

        ob_start(); ?>
            <script>
                jQuery('#<?= $this->uniqId($name).'_REMOVE'; ?>').click(function(e){
                    e.preventDefault();
                    jQuery('#<?= $this->uniqId($name).'_DELETE'; ?>').val(1);
                    jQuery('#<?= $this->uniqId($name).'_REMOVE'; ?>').hide();
                    jQuery('#<?= $this->uniqId($name).'_PREVIEW'; ?>').hide();
                    jQuery('#<?= $this->uniqId($name); ?>').show();
                });

                <?php if ($preview) { ?>
                    jQuery('#<?= $this->uniqId($name); ?>').hide();
                <?php } ?>
            </script>
            <?php
        $footer_html = ob_get_contents();
        ob_end_clean();

        $this->addScript($footer_html);

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeTextarea($name, $value)
    {
        $field = $this->getField($name);

        $html = '';
        $html .= '<textarea ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= $this->makeAttr('placeholder', $name);
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);
        $html .= $this->makeAttr('rows', $name);
        $html .= $this->makeAttr('cols', $name);
        $html .= '>';
        $html .= self::escWysiwyg($value);
        $html .= '</textarea>';

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeWysiwyg($name, $value)
    {
        $field = $this->getField($name);
        $html = $this->makeTextarea($name, $value);

        $footer_html = '<script src="https://cloud.tinymce.com/stable/tinymce.min.js?apiKey='.config('app.tinymce').'"></script>';
        $this->addScript($footer_html, 'tinymce');

        ob_start(); ?>
            <script>
                tinymce.init({
                    selector: 'textarea[name="<?= $this->makeName($name); ?>"]',
                    browser_spellcheck: true,
                    menubar: false,
                    plugins : 'image link code lists',
                    toolbar: 'bold italic | bullist numlist | removeformat | code',
                    height: '<?= self::escHtml($field->attrs['height'] ?? 170); ?>',
                    convert_urls : true,
                    relative_urls: false
                });
            </script>
            <?php
        $footer_html = ob_get_clean();
        $this->addScript($footer_html);

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeSelect($name, $value)
    {
        $field = $this->getField($name);

        $html = '';
        $html .= '<select ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);
        $html .= '>';
        $html .= "\n";

        if (isset($field->attrs['default'])) {
            $html .= '<option value="">'.$field->attrs['default'].'</option>';
            $html .= "\n";
        } elseif (!$this->isRequired($name) || ($field->attrs['nullable'] ?? null)) {
            $html .= '<option value="">[please select]</option>';
            $html .= "\n";
        }

        foreach ($field->options as $key => $text) {
            if (is_array($text)) { //optgroups

                $html .= '<optgroup label="'.$key.'">';
                $html .= "\n";

                foreach ($text as $key => $text) {
                    $html .= '<option ';
                    $html .= 'value="'.$key.'" ';
                    $html .= $this->makeAttr('readonly', $name);
                    $html .= $this->makeAttr('disabled', $name);

                    if ($key == $value) {
                        $html .= ' selected="selected"';
                    } else {
                        $html .= '';
                    }

                    $html .= '>';
                    $html .= $text;
                    $html .= '</option>';
                    $html .= "\n";
                }

                $html .= '</optgroup>';
                $html .= "\n";
            } else {
                $html .= '<option ';
                $html .= 'value="'.$key.'" ';
                $html .= $this->makeAttr('readonly', $name);
                $html .= $this->makeAttr('disabled', $name);

                if ($key == $value) {
                    $html .= ' selected="selected"';
                } else {
                    $html .= '';
                }

                $html .= '>';
                $html .= $text;
                $html .= '</option>';
                $html .= "\n";
            }
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeRadiobuttons($name, $value)
    {
        $field = $this->getField($name);

        $html = '';

        foreach ($field->options as $key => $text) {
            $html .= '<label>';
            $html .= '<input type="radio" ';
            $html .= 'name="'.$this->makeName($name).'" ';
            $html .= 'value="'.$key.'" ';
            $html .= 'class="'.$this->errorClass($name).'" ';
            $html .= $this->makeAttr('readonly', $name);
            $html .= $this->makeAttr('disabled', $name);

            if ($key == $value) {
                $html .= ' checked="checked"';
            } else {
                $html .= '';
            }

            $html .= '>';
            $html .= $text;
            $html .= '</label>';
            $html .= "\n";
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeCheckbox($name, $value)
    {
        $field = $this->getField($name);

        $html = '';

        $html .= '<input type="checkbox" ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= 'value="1" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);

        if ($value) {
            $html .= ' checked="checked"';
        } else {
            $html .= '';
        }

        $html .= '>';
        $html .= "\n";

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    protected function makeDatePicker($name, $value)
    {
        $field = $this->getField($name);

        if ($value && !$value instanceof DateTime) {
            $value = new DateTime($value);
        }

        if (isset($field->attrs['format'])) {
            $format = $field->attrs['format'];
            $format = str_replace('yyyy', 'Y', $format);
            $format = str_replace('yy', 'y', $format);
            $format = str_replace('mmmm', 'F', $format);
            $format = str_replace('mmm', 'M', $format);
            $format = str_replace('mm', '@', $format);
            $format = str_replace('m', 'n', $format);
            $format = str_replace('@', 'm', $format);
            $format = str_replace('dddd', 'l', $format);
            $format = str_replace('ddd', 'D', $format);
            $format = str_replace('dd', '@', $format);
            $format = str_replace('d', 'j', $format);
            $format = str_replace('@', 'd', $format);
        } else {
            $format = 'j F Y';
        }

        if ($value) {
            $value_display = $value->format($format);
        } else {
            $value_display = '';
        }

        $html = '';
        $html = '<input ';
        $html .= 'type="text" ';
        $html .= 'name="'.$this->makeName($name).'" ';
        $html .= 'value="'.self::escHtml($value_display).'" ';
        $html .= 'class="'.$this->errorClass($name).'" ';
        $html .= 'id="'.$this->uniqId($name).'" ';
        $html .= $this->makeAttr('placeholder', $name);
        $html .= $this->makeAttr('readonly', $name);
        $html .= $this->makeAttr('disabled', $name);
        $html .= '>';

        if (isset($field->attrs['format'])) {
            $format = $field->attrs['format'];
        } else {
            $format = 'd mmmm yyyy';
        }

        if (isset($field->attrs['num_years'])) {
            $num_years = $field->attrs['num_years'];
        } else {
            $num_years = 6;
        }

        if (isset($field->attrs['min'])) {
            $min = ', min: new Date('.date('Y', $field->attrs['min']).', '.(date('n', $field->attrs['min']) - 1).', '.date('j', $field->attrs['min']).')';
        } else {
            $min = '';
        }

        if (empty($field->attrs['max'])) {
            $max = '';
        } elseif ($field->attrs['max'] == 'today') {
            $max = ', max: true';
        } else {
            $max = ', max: new Date('.date('Y', $field->attrs['max']).', '.(date('n', $field->attrs['max']) - 1).', '.date('j', $field->attrs['max']).')';
        }

        ob_start(); ?>
            <script>
                jQuery('#<?= $this->uniqId($name); ?>').pickadate({
                    container: 'body',
                    selectYears: '<?= $num_years; ?>',
                    selectMonths: true,
                    format: '<?= $format; ?>',
                    formatSubmit: 'yyyy/mm/dd',
                    hiddenName: true
                    <?= $min; ?>
                    <?= $max; ?>
                });
            </script>
            <?php
        $footer_html = ob_get_contents();
        ob_end_clean();

        $this->addScript($footer_html);

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    protected static function escHtml($html)
    {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $html
     * @return string
     */
    protected static function escWysiwyg($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $clean = $purifier->purify($html);
        return $clean;
    }

    /**
     * @param string $html
     * @param string $key
     * @return bool
     */
    protected function addScript($html, $key = null)
    {
        if ($key) {
            $this->scripts[$key] = $html;
        } else {
            $this->scripts[] = $html;
        }
    }

    /**
     * @param string $lang
     */
    public static function setLang($lang)
    {
        self::$_lang = $lang;
    }

    /**
     * @return bool
     */
    public function getTranslateErrors(): bool
    {
        return $this->translate_errors;
    }

    /**
     * @param bool $translate_errors
     */
    public function setTranslateErrors(bool $translate_errors): void
    {
        $this->translate_errors = $translate_errors;
    }
}
