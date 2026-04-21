<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessTypeToBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('name')
                ->comment('Type of business: restaurant_bar, repair_shop, manufacturing, essential_business, hotel_management, hospital_management, school_management, gym_management');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
}

