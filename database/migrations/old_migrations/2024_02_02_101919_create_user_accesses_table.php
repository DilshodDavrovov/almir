<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAccessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_accesses', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade')->index("acc_member_id");
            $table->foreignId('type_id')->constrained('drug_types')->onUpdate('cascade')->onDelete('cascade')->index("acc_type_id");
            $table->timestamps();
            //SETTING THE PRIMARY KEYS
            $table->primary(['member_id', 'type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_accesses');
    }
}
