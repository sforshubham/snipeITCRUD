<?php

namespace App\Console\Commands;

use File;
use Illuminate\Console\Command;

class CrudViewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icrud:view
                            {name : The name of the Crud.}
                            {--fields= : The field names for the form.}
                            {--view-path= : The name of the view path.}
                            {--route-group= : Prefix of the route group.}
                            {--pk=id : The name of the primary key.}
                            {--validations= : Validation rules for the fields.}
                            {--form-helper=html : Helper for the form.}
                            {--custom-data= : Some additional values to use in the crud.}
                            {--localize=no : Localize the view? yes|no.}
                            {--search : Fields to apply searching.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create views for the Crud.';

    /**
     * View Directory Path.
     *
     * @var string
     */
    protected $viewDirectoryPath;

    /**
     * View Filters Directory Path.
     *
     * @var string
     */
    protected $viewFiltersDirPath;

    /**
     *  Form field types collection.
     *
     * @var array
     */
    protected $typeLookup = [
        'string' => 'text',
        'char' => 'text',
        'varchar' => 'text',
        'text' => 'textarea',
        'mediumtext' => 'textarea',
        'longtext' => 'textarea',
        'json' => 'textarea',
        'jsonb' => 'textarea',
        'binary' => 'textarea',
        'password' => 'password',
        'email' => 'email',
        'number' => 'number',
        'integer' => 'number',
        'bigint' => 'number',
        'mediumint' => 'number',
        'tinyint' => 'number',
        'smallint' => 'number',
        'decimal' => 'number',
        'double' => 'number',
        'float' => 'number',
        'date' => 'date',
        'datetime' => 'datetime-local',
        'timestamp' => 'datetime-local',
        'time' => 'time',
        'radio' => 'radio',
        'boolean' => 'radio',
        'enum' => 'select',
        'select' => 'select',
        'file' => 'file',
    ];

    /**
     * Variables that can be used in stubs
     *
     * @var array
     */
    protected $vars = [
        'dynamicFiltersForm',
        'formFields',
        'formFieldsHtml',
        'varName',
        'crudName',
        'crudNameCap',
        'crudNameSingular',
        'primaryKey',
        'modelName',
        'modelNameCap',
        'viewName',
        'routePrefix',
        'routePrefixCap',
        'routeGroup',
        'formHeadingHtml',
        'formBodyHtml',
        'viewTemplateDir',
        'formBodyHtmlForShowView',
    ];

    /**
     * Form's fields.
     *
     * @var array
     */
    protected $formFields = [];

    /**
     * Html of Form's fields.
     *
     * @var string
     */
    protected $formFieldsHtml = '';

    /**
     * Number of columns to show from the table. Others are hidden.
     *
     * @var integer
     */
    protected $defaultColumnsToShow = 3;

    /**
     * Variable name with first letter in lowercase
     *
     * @var string
     */
    protected $varName = '';

    /**
     * Name of the Crud.
     *
     * @var string
     */
    protected $crudName = '';

    /**
     * Crud Name in capital form.
     *
     * @var string
     */
    protected $crudNameCap = '';

    /**
     * Crud Name in singular form.
     *
     * @var string
     */
    protected $crudNameSingular = '';

    /**
     * Primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Name of the Model.
     *
     * @var string
     */
    protected $modelName = '';

    /**
     * Name of the Model with first letter in capital
     *
     * @var string
     */
    protected $modelNameCap = '';

    /**
     * Name of the View Dir.
     *
     * @var string
     */
    protected $viewName = '';

    /**
     * Prefix of the route
     *
     * @var string
     */
    protected $routePrefix = '';

    /**
     * Prefix of the route with first letter in capital letter
     *
     * @var string
     */
    protected $routePrefixCap = '';

    /**
     * Name or prefix of the Route Group.
     *
     * @var string
     */
    protected $routeGroup = '';

    /**
     * Html of the form heading.
     *
     * @var string
     */
    protected $formHeadingHtml = '';

    /**
     * Html of the form body.
     *
     * @var string
     */
    protected $formBodyHtml = '';

    /**
     * Html of view to show.
     *
     * @var string
     */
    protected $formBodyHtmlForShowView = '';

    /**
     * User defined values
     *
     * @var array
     */
    protected $customData = [];

    /**
     * Template directory where views are generated
     *
     * @var string
     */
    protected $viewTemplateDir = '';

    /**
     * Delimiter used for replacing values
     *
     * @var array
     */
    protected $delimiter;

    /**
     * Dynamic Filters Html
     *
     * @var string
     */
    protected $dynamicFiltersForm = '';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

        if (config('crudgenerator.view_columns_number')) {
            $this->defaultColumnsToShow = config('crudgenerator.view_columns_number');
        }

        $this->delimiter = config('crudgenerator.custom_delimiter')
            ? config('crudgenerator.custom_delimiter')
            : ['%%', '%%'];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $formHelper = $this->option('form-helper');
        $this->viewDirectoryPath = config('crudgenerator.custom_template')
            ? config('crudgenerator.path') . 'views/' . $formHelper . '/'
            : __DIR__ . '/../stubs/views/' . $formHelper . '/';

        $this->viewFiltersDirPath = $this->viewDirectoryPath . 'filters' . '/';
        $this->crudName = strtolower($this->argument('name'));
        $this->varName = lcfirst($this->argument('name'));
        $this->crudNameCap = ucwords($this->crudName);
        $this->crudNameSingular = str_singular($this->crudName);
        $this->modelName = str_singular($this->argument('name'));
        $this->modelNameCap = ucfirst($this->modelName);
        $this->customData = $this->option('custom-data');
        $this->primaryKey = $this->option('pk');
        $this->routeGroup = ($this->option('route-group'))
            ? $this->option('route-group') . '/'
            : $this->option('route-group');
        $this->routePrefix = ($this->option('route-group')) ? $this->option('route-group') : '';
        $this->routePrefixCap = ucfirst($this->routePrefix);
        $this->viewName = snake_case($this->argument('name'), '-');

        $viewDirectory = config('view.paths')[0] . '/';
        if ($this->option('view-path')) {
            $this->userViewPath = $this->option('view-path');
            $path = $viewDirectory . $this->userViewPath . '/' . $this->viewName . '/';
        } else {
            $path = $viewDirectory . $this->viewName . '/';
        }

        $this->viewTemplateDir = isset($this->userViewPath)
            ? $this->userViewPath . '.' . $this->viewName
            : $this->viewName;

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $fields = $this->option('fields');
        $fieldsArray = explode(';', $fields);
        $searches = rtrim($this->option('search'), ';');

        $this->formFields = [];

        $validations = $this->option('validations');

        if ($fields) {
            $x = 0;

            $textSearch = 0;
            $filtersFormHtml = '';
            $filtersFieldHtml = '';
            $hasFilters = 0;

            $filterFieldsArray = $this->structureFilterFields($searches);
            foreach ($fieldsArray as $item) {
                $itemArray = explode('#', $item);

                $this->formFields[$x]['name'] = trim($itemArray[0]);
                $this->formFields[$x]['type'] = trim($itemArray[1]);
                $this->formFields[$x]['required'] = preg_match('/' . $itemArray[0] . '/', $validations) ? true : false;

                if (($this->formFields[$x]['type'] === 'select'
                    || $this->formFields[$x]['type'] === 'enum')
                    && isset($itemArray[2])
                ) {
                    $options = trim($itemArray[2]);
                    $options = str_replace('options=', '', $options);

                    $this->formFields[$x]['options'] = $options;
                }

                if (isset($filterFieldsArray[$itemArray[0]])) {
                    switch ($filterFieldsArray[$itemArray[0]]) {
                        case 'full_text':
                            $textSearch = 1;
                            break;

                        case 'single_select':
                            $selectStub = File::get($this->viewFiltersDirPath . 'select-field.blade.stub');
                            $filtersFieldHtml .= $this->generateSelectFieldHtml($selectStub, $this->formFields[$x]);
                            break;

                        case 'multi_select':
                            $multiSelectStub = File::get($this->viewFiltersDirPath . 'multi-select-field.blade.stub');
                            $filtersFieldHtml .= $this->generateSelectFieldHtml($multiSelectStub, $this->formFields[$x]);
                            break;
                    }
                }

                $x++;
            }

            if ($textSearch == 1 || !empty($filtersFieldHtml)) {
                $filtersFormStub = File::get($this->viewFiltersDirPath . 'filter-form.blade.stub');
                if ($textSearch == 1) {
                    $searchBoxHtml = File::get($this->viewFiltersDirPath . 'input-field.blade.stub');
                    $filtersFieldHtml = $searchBoxHtml.$filtersFieldHtml;
                }
                $this->dynamicFiltersForm = $this->replaceDynamicFilterFields($filtersFormStub, $filtersFieldHtml);
            }
        }

        foreach ($this->formFields as $item) {
            $this->formFieldsHtml .= $this->createField($item);
        }

        $i = 0;
        foreach ($this->formFields as $key => $value) {
            if ($i == $this->defaultColumnsToShow) {
                break;
            }

            $field = $value['name'];
            $label = ucwords(str_replace('_', ' ', $field));
            if ($this->option('localize') == 'yes') {
                $label = '{{ trans(\'' . $this->crudName . '.' . $field . '\') }}';
            }
            $this->formHeadingHtml .= '<th>' . $label . '</th>';
            $this->formBodyHtml .= '<td>{{ $item->' . $field . ' }}</td>';
            $this->formBodyHtmlForShowView .= '<tr><th> ' . $label . ' </th><td> {{ $%%crudNameSingular%%->' . $field . ' }} </td></tr>';

            $i++;
        }

        $this->templateStubs($path);

        $this->info('View created successfully.');
    }

    /**
     * Default template configuration if not provided
     *
     * @return array
     */
    private function defaultTemplating()
    {
        return [
            'index' => ['dynamicFiltersForm', 'formHeadingHtml', 'formBodyHtml', 'crudName', 'crudNameCap', 'modelName', 'viewName', 'routeGroup', 'primaryKey'],
            'form' => ['formFieldsHtml'],
            'create' => ['crudName', 'crudNameCap', 'modelName', 'modelNameCap', 'viewName', 'routeGroup', 'viewTemplateDir'],
            'edit' => ['crudName', 'crudNameSingular', 'crudNameCap', 'modelNameCap', 'modelName', 'viewName', 'routeGroup', 'primaryKey', 'viewTemplateDir'],
            'show' => ['formHeadingHtml', 'formBodyHtml', 'formBodyHtmlForShowView', 'crudName', 'crudNameSingular', 'crudNameCap', 'modelName', 'viewName', 'routeGroup', 'primaryKey'],
        ];
    }

    /**
     * Generate files from stub
     *
     * @param $path
     */
    protected function templateStubs($path)
    {
        $dynamicViewTemplate = config('crudgenerator.dynamic_view_template')
            ? config('crudgenerator.dynamic_view_template')
            : $this->defaultTemplating();

        foreach ($dynamicViewTemplate as $name => $vars) {
            $file = $this->viewDirectoryPath . $name . '.blade.stub';
            $newFile = $path . $name . '.blade.php';
            if (!File::copy($file, $newFile)) {
                echo "failed to copy $file...\n";
            } else {
                $this->templateVars($newFile, $vars);
                $this->userDefinedVars($newFile);
            }
        }
    }

    /**
     * Update specified values between delimiter with real values
     *
     * @param $file
     * @param $vars
     */
    protected function templateVars($file, $vars)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        foreach ($vars as $var) {
            $replace = $start . $var . $end;
            if (in_array($var, $this->vars)) {
                File::put($file, str_replace($replace, $this->$var, File::get($file)));
            }
        }
    }

    /**
     * Update custom values between delimiter with real values
     *
     * @param $file
     */
    protected function userDefinedVars($file)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        if ($this->customData !== null) {
            $customVars = explode(';', $this->customData);
            foreach ($customVars as $rawVar) {
                $arrayVar = explode('=', $rawVar);
                File::put($file, str_replace($start . $arrayVar[0] . $end, $arrayVar[1], File::get($file)));
            }
        }
    }

    /**
     * Form field wrapper.
     *
     * @param  string $item
     * @param  string $field
     *
     * @return string
     */
    protected function wrapField($item, $field)
    {
        $formGroup = File::get($this->viewDirectoryPath . 'form-fields/wrap-field.blade.stub');

        $labelText = "'" . ucwords(strtolower(str_replace('_', ' ', $item['name']))) . "'";

        if ($this->option('localize') == 'yes') {
            $labelText = 'trans(\'' . $this->crudName . '.' . $item['name'] . '\')';
        }

        return sprintf($formGroup, $item['name'], $labelText, $field);
    }

    /**
     * Form field generator.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createField($item)
    {
        switch ($this->typeLookup[$item['type']]) {
            case 'password':
                return $this->createPasswordField($item);
            case 'datetime-local':
            case 'time':
                return $this->createInputField($item);
            case 'radio':
                return $this->createRadioField($item);
            case 'textarea':
                return $this->createTextareaField($item);
            case 'select':
            case 'enum':
                return $this->createSelectField($item);
            default: // text
                return $this->createFormField($item);
        }
    }

    /**
     * Create a specific field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createFormField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->viewDirectoryPath . 'form-fields/form-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a password field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createPasswordField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->viewDirectoryPath . 'form-fields/password-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a generic input field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createInputField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->viewDirectoryPath . 'form-fields/input-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a yes/no radio button group using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createRadioField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $markup = File::get($this->viewDirectoryPath . 'form-fields/radio-field.blade.stub');
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a textarea field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createTextareaField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->viewDirectoryPath . 'form-fields/textarea-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a select field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createSelectField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->viewDirectoryPath . 'form-fields/select-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'options' . $end, $item['options'], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Structure filterable fields into "field_name:filter_type" fashion
     *
     * @param $filtersString
     * @author Shubham Goel <shubham.goel@instantsys.com>
     *
     * @return $this
     */
    protected function structureFilterFields($filtersString)
    {
        $filtersArray = [];
        if ($filtersString) {
            $filtersArray = explode(';', $filtersString);
            foreach ($filtersArray as $i => $val) {
                list($field_name, $search_type) = explode('#', $val);
                $filtersArray[$field_name] = $search_type;
                unset($filtersArray[$i]);
            }
        }
        return $filtersArray;
    }

    /**
     * Generate dynamic Select Field Html from stub
     *
     * @param $selectFieldStub
     * @param $fieldData
     * @author Shubham Goel <shubham.goel@instantsys.com>
     *
     * @return $this
     */
    protected function generateSelectFieldHtml($selectFieldStub, $fieldData)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $selectFieldHtml = str_replace($start . 'options' . $end, $fieldData['options'], $selectFieldStub);
        $selectFieldHtml = str_replace($start . 'itemName' . $end, $fieldData['name'], $selectFieldHtml);

        return $selectFieldHtml;
    }

    /**
     * Replace dynamic filter Fields
     *
     * @param $filtersFormStub
     * @param $filtersFieldHtml
     * @author Shubham Goel <shubham.goel@instantsys.com>
     *
     * @return $this
     */
    protected function replaceDynamicFilterFields($filtersFormStub, $filtersFieldHtml)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $filtersFormHtml = str_replace($start . 'dynamicFilterFields' . $end, $filtersFieldHtml, $filtersFormStub);

        return $filtersFormHtml;
    }
}
