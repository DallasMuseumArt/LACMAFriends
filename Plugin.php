<?php namespace DMA\LACMA;

use App;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\Event;
use DMA\Friends\Models\Usermeta as Metadata;

/**
 * LACMA Friends Plugin Information File
 *
 * @package DMA\LACMA
 * @author Kristen Arnold
 */
class Plugin extends PluginBase
{
    /** 
     * @var array Plugin dependencies
     */
    public $require = [
        'DMA.Friends'
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'LACMA Friends',
            'description' => 'Customizations for LACMA NextGEN platform',
            'author'      => 'Dallas Museum of Art',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        // Extend User fields
        $context = $this;
        Event::listen('backend.form.extendFields', function($widget) use ($context){
            $context->extendedUserFields($widget);
        }); 

        // Register service providers
        App::register('Maatwebsite\Excel\ExcelServiceProvider');

        // Register aliases
        $alias = AliasLoader::getInstance();
        $alias->alias('Excel', 'Maatwebsite\Excel\Facades\Excel');
    }


    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->registerConsoleCommand('lacma.import-nextgen-users', 'DMA\LACMA\Commands\ImportNextGen');
    }

    /**
     * Extend User fields in Rainlab.User plugin
     * @param mixed $widget
     */
    private function extendedUserFields($widget)
    {
        if (!$widget->getController() instanceof \RainLab\User\Controllers\Users) return;
        if ($widget->getContext() != 'update') return;
        
        // Make sure the User metadata exists for this user.
        if (!Metadata::getFromUser($widget->model)) return;
        
        $widget->addFields([
            'metadata[middle_name]' => [
                'label' => 'Middle Name',
                'tab'   => 'Metadata',
                'span'  => 'left',
            ],
            'metadata[guardian_name]' => [
                'label' => 'Parent/Guardian Full Name',
                'tab'   => 'Metadata',
                //'span'  => 'left',
            ],
            'metadata[expires_on]' => [
                'label' => 'Expires On',
                'tab'   => 'Metadata',
                'span'  => 'left',
            ],
        ], 'primary');        
    }
}