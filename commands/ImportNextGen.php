<?php
namespace DMA\LACMA\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;
use RainLab\User\Models\User;
use RainLab\User\Models\Country;
use RainLab\User\Models\State;
use DMA\Friends\Models\Usermeta;
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
     * @var string A count of processed records
     */
    protected $counter = 0;

    /**
     * @var string A count of records that failed to process
     */
    protected $error = 0;

    /**
     * {@inheritDoc}
     */
    public function fire()
    {
        if ($this->option('clean-import') && $this->confirm("Deleting all accounts. Are you sure? [Y]es|[N]o")) {

            $users = User::all();
            foreach ($users as $user) {
                $user->delete();
            }

            $this->info("All accounts deleted");
        }

        $file = $this->argument('file');

        $this->info('Attempting to import users from ' . $file);

        Excel::load($file, function($reader) {
            $results = $reader->get();

            foreach ($results as $result) {
                $this->processResult($result);
            }
        });

        $this->info($this->error . " accounts failed to import");
        $this->info("Successfully imported " . $this->counter . " records");
    }

    /**
     * @param object $result
     * A CSV object to process and import into October
     */
    protected function processResult($result) 
    {
        // bypass accounts with no zip for now until we know how to handle passwords for them
        if (empty($result->cnadrprf_zip)) {
            $name = empty($result->cnadrprfph_1_01_phone_number) ? 
                $result->cnbio_first_name . " " . $result->cnbio_last_name : $result->cnadrprfph_1_01_phone_number;
            $this->error($name . ": No zip code");
            $this->error++;
            return;
        }

        $userKeys = [
            'cnadrprf_addrline1'            => 'street_addr',
            'cnadrprf_city'                 => 'city',
            'cnadrprf_state'                => 'state',
            'cnadrprf_zip'                  => 'zip',
            'cnadrprf_contrylongdscription' => 'country',
            'cnadrprfph_1_01_phone_number'  => 'email',
            'cnmem_1_01_date_joined'        => 'created_at',
        ];

        $metaKeys = [
            'nexgenid'                  => 'current_member_number',
            'cnbio_first_name'          => 'first_name',
            'cnbio_middle_name'         => 'middle_name',
            'cnbio_last_name'           => 'last_name',
            'cnbio_birth_date'          => 'birth_date',
            'cnmem_1_01_cur_expires_on' => 'expires_on',
            'cnrelind_1_01_name'        => 'guardian_name',
        ];

        $user = new User;
        $usermeta = new Usermeta;

        // Populate user object
        foreach($userKeys as $oldKey => $key) {

            if (in_array($key, ['created_at', 'expires_on'])) {
                $result->{$oldKey} = $this->formatDate($result->{$oldKey});
            }

            if ($key == 'country') {
                $result->{$oldKey} = Country::where('name', $result->{$oldKey})->first();
            }

            if ($key == 'state') {
                $result->{$oldKey} = State::where('code', $result->{$oldKey})->first();
            }

            $user->{$key} = $result->{$oldKey};
        }

        // Populate metadata object
        foreach($metaKeys as $oldKey => $key) {
            $usermeta->{$key} = $result->{$oldKey};
        }

        // Save additional user fields
        if (!$user->email) {
            $user->email = $this->seedEmail($result);
        }

        $user->is_activated             = true;
        $user->password                 = $result->cnadrprf_zip;
        $user->password_confirmation    = $result->cnadrprf_zip;

        try {         
            $user->save();
            $user->metadata()->save($usermeta);
            $this->counter++;
        } catch(\October\Rain\Database\ModelException $e) {
            $this->error($user->email . ": " . $e->getMessage());
            $this->error++;
        }
        
    }

    /**
     * @param array $data
     * The data object for a record
     *
     * @return string
     * Returns a dummy email with the provided seed
     */
    private function seedEmail($data) {
        $email = $data->cnbio_first_name . '-' . $data->cnbio_last_name . '-' . md5($data->nexgenid);
        $email = substr($email, 0, 53);
        $email = str_replace(" ", '', $email);
        return $email . "@lacma.null";
    }

    /**
     * @param string $date
     *  The CSV provides dates in MM/DD/YY format, convert to a valid
     *  string that Carbon can handle
     * 
     * @return string
     * The date in "Y-m-d H:i:s" format
     */
    private function formatDate($date) {
        $time = strtotime($date);
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * {@inheritDoc}
     */
    public function getArguments()
    {
        return [
            ['file', InputArgument::REQUIRED, 'The CSV file to import', null],
        ];  
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return [
            ['clean-import', null, InputOption::VALUE_NONE, 'Delete all accounts before importing']
        ];
    }
}
