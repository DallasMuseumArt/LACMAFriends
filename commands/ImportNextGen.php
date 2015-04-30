<?php
namespace DMA\LACMA\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;
use RainLab\User\Models\User;
use Excel;

/**
 * Import NextGEN users from csv
 *
 * @package DMA\LACMA\Commands
 * @author Kristen Arnold
 */
class ImportNextGen extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'lacma:import-nextgen-users';

    /**
     * @var string The console command description.
     */
    protected $description = 'Syncronize nextgen data into October';

    /**
     * @inheritdoc
     */
    public function fire()
    {
        $file = $this->argument('file');

        $this->info('Attempting to import users from ' . $file);

        Excel::load($file, function($reader) {
            $results = $reader->get();

            var_dump($results);
        });
    }

    public function getArguments()
    {
        return [
            ['file', InputArgument::REQUIRED, 'The CSV file to import', null],
        ];  
    }
}
