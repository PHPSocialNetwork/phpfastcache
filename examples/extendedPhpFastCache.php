<?php
namespace MyCustom\Project;
use phpFastCache\Drivers\Files\Driver as FilesDriver;

/**
 * Class extendsPhpFastCache
 * @package MyCustom\Project
 */
class extendedPhpFastCache extends FilesDriver
{
    public function __construct(array $config = [])
    {
        $config['path'] = 'your/custom/path/where/files/will/be/written';
        parent::__construct($config);
        /**
         * That's all !! Your cache class is ready to use
         */
    }
}