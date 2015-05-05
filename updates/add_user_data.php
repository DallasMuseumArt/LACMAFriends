<?php namespace DMA\LACMA\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddUserData extends Migration {

    /** 
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        Schema::table('dma_friends_usermetas', function($table)
        {   
            $table->string('guardian_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->timestamp('expires_on')->nullable();
            $table->string('razorsedge_id');
            $table->string('email')->nullable();
        });
    }   


    /** 
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {   
        Schema::table('dma_friends_usermetas', function($table)
        {
            $table->dropColumn('guardian_name');
            $table->dropColumn('middle_name');
            $table->dropColumn('expires_on');
            $table->dropColumn('razorsedge_id');
            $table->dropColumn('email');
        });
    }   

}
