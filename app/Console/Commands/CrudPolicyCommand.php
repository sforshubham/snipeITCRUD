<?php

namespace App\Console\Commands;

use File;
use Illuminate\Console\GeneratorCommand;

class CrudPolicyCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icrud:policy
                            {name : The name of the Model policy. eg: UserPolicy}
                            {--force : Overwrite already existing policy.}
                            {--policy-namespace= : Namespace of the policy.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';

    protected $name = '';
    protected $modelName = '';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('crudgenerator.custom_template')
        ? config('crudgenerator.path') . '/policy.stub'
        : __DIR__ . '/../stubs/policy.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . 'Policies';
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        if ($this->option('force')) {
            return false;
        }
        return parent::alreadyExists($rawName);
    }

    /**
     * Build the model class with the given name.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $this->name = $this->argument('name');
        $this->modelName = str_replace('policy', '', strtolower($this->name));
        $columnName = str_plural($this->modelName);
        $this->modelName = ucfirst($this->modelName);

        $ret = $this->replaceColumnName($stub, $columnName);

        $this->updateAuthServiceProviderClass();
        return $ret->replaceClass($stub, $this->name);

    }

    /**
     * Replace the column name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $table
     *
     * @return $this
     */
    protected function replaceColumnName(&$stub, $name)
    {
        $stub = str_replace('{{columnName}}', $name, $stub);

        return $this;
    }

    /**
     * Update AuthServiceProviderClass based on created model policy object
     *
     * @return void
     */
    public function updateAuthServiceProviderClass()
    {
        $authSPPath = app_path('Providers/AuthServiceProvider.php');
        $authSPContent = $this->files->get($authSPPath);
        $name = $this->name;
        $modelName = $this->modelName;

        $authSPContent = str_replace(
            "namespace App\Providers;
",
            "namespace App\Providers;\n\nuse App\Models\\$modelName;\nuse App\Policies\\$name;",
            $authSPContent
        );

        $authSPContent = str_replace(
            "    protected \$policies = [
",
            "    protected \$policies = [\n        $modelName::class => $name::class,\n",
            $authSPContent
        );
        $this->files->put($authSPPath, $authSPContent);
        $this->info('AuthServiceProvider class has been updated.');
    }
}
