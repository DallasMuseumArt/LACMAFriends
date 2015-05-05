<?php
namespace DMA\LACMA\Commands;

use Illuminate\Console\Command;
use RainLab\User\Models\User;
use Cms\Classes\MediaLibrary;
use Excel;

/**
 * Export NextGEN users from csv
 *
 * @package DMA\LACMA\Commands
 * @author Kristen Arnold
 */
class ExportNextGen extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'lacma:export-nextgen-users';

    /**
     * @var string The console command description.
     */
    protected $description = 'Export nextgen data from October';

    /**
     * @var int The number of user records to process per chunk
     */
    protected $chunkLimit = 200;

    /**
     * {@inheritDoc}
     */
    public function fire()
    {
        $filename = "nextgen-export-" . date('Y-m-d');

        $data = Excel::create($filename, function($excel) {

            $excel->sheet('Sheet', function($sheet) {

                User::chunk($this->chunkLimit, function($users) use ($sheet) {
                    foreach($users as $user) {
                        $this->processUser($user, $sheet);
                    }
                });
            });

        })->store('csv', storage_path('app/media/NextGENExports'), true);

        $library = MediaLibrary::instance();
        $library->resetCache();

        $this->info("The export has completed. You can access the file at '" . $data['full'] . '"');
        $this->info("or download it from the media manager in the administrative backend");
    }

    protected function processUser($user, $sheet)
    {
        static $firstRun = true;

        $userAttr = (array)$user->attributes;
        $usermetaAttr = (array)$user->metadata->attributes;

        // Exclude certain fields
        unset($userAttr['password']);
        unset($userAttr['email']);

        $data = array_merge($usermetaAttr, $userAttr);

        $keys = array_keys($data);

        if ($firstRun) {
            $sheet->appendRow($keys);
            $firstRun = false;
        }

        $sheet->appendRow($data);
    }
}
