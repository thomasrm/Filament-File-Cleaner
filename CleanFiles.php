<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all the files in storage that are not used anymore, according to all the models in the app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // detect all the files in storage that are not used anymore
        // and delete them

        // get all the files in storage_path('app/public')
        $splFiles = File::allFiles(storage_path('app/public'));
        $files = [];
        foreach ($splFiles as $splFile) {
            $files[] = $splFile->getRelativePathname();
        }

        // for each Model, browse all the fields and test if they are existing files or not
        // if they are existing files, add them to an array
        $usedFiles = [];

        // get all the models in the app
        $models = File::allFiles(app_path('Models'));

        // for each model in the app (except the abstract ones)
        foreach ($models as $model) {
            $modelName = $model->getBasename('.php');
            if ($modelName !== 'Model' && $modelName !== 'BaseModel') {
                // get the model class
                $modelClass = 'App\\Models\\' . $modelName;
                
                // check the class exists
                if (!class_exists($modelClass)) {
                    $this->warn('Class ' . $modelClass . ' does not exist');
                    continue;
                }
                
                // get all the instances of the model
                $instances = $modelClass::all();
                // for each instance, get all the fields
                foreach ($instances as $instance) {
                    $fields = $instance->getAttributes();

                    // for each field, test if it is a file
                    foreach ($fields as $field) {
                        // if the field is an array, test if it contains files, do it recursively
                        if ($this->isJson($field) && is_array(json_decode($field, true))) {
                            $field = json_decode($field, true);
                            if (count($field))
                                $usedFiles = array_merge($usedFiles, $this->arrayContainsFiles($field));
                        } elseif ($field) {
                            if ($this->fileExists($field)) {
                                // if it is a file, add it to the array
                                $usedFiles[] = $field;
                            }
                        }
                    }
                }
            }
        }

        // get the files that are not used anymore
        $filesToDelete = array_diff($files, $usedFiles);

        // delete the files
        foreach ($filesToDelete as $file) {
            if (File::delete(storage_path('app/public/' . $file))) {
                $this->info('File ' . $file . ' deleted');
            } else {
                $this->error('File ' . $file . ' not deleted');
            }
        }
    }

    /**
     * Check a file exists and is not a directory
     * @param string $file
     * @return bool
     */
    private function fileExists($file)
    {
        $path = storage_path('app/public/' . $file);
        return File::exists($path) && !File::isDirectory($path);
    }

    /**
     * Check if a string is a json
     * @param string $string
     * @return bool
     */
    private function isJson($string) {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
     }

    /**
     * Recursively check if an array contains files, and add them to an array
     * @param array|string $array
     * @param array $usedFiles
     * @return array
     */
    private function arrayContainsFiles($array, &$usedFiles = [])
    {

        if ($this->isJson($array)) {
            $array = json_decode($array, true);
        }

        if (is_array($array)) {
            foreach ($array as $field) {
                    // if the field is an array, test if it contains files, do it recursively
                    $this->arrayContainsFiles($field, $usedFiles);
            }
        }
        elseif ($this->fileExists($array)) {
                // if it is a file, add it to the array
                $usedFiles[] = $array;
            }

        return $usedFiles;
    }
}
