<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->unique()->index("name");
            $table->foreignId('dt_id')->constrained('drug_types')->onDelete('cascade')->comment("Drug Type ID")->index("dt_id");
            $table->integer('counter')->unsigned()->unique()->index("counter");
            $table->foreignId('trademark_id')->constrained('trademarks')->onDelete('cascade')->comment("trademark  ID")->index("trademark_id");
            $table->foreignId('di_id')->constrained('drug_inns')->onDelete('cascade')->comment("Drug INN  ID")->index("drug_inns");
            $table->foreignId('df_id')->constrained('drug_forms')->onDelete('cascade')->comment("Drug Forms  ID")->index("drug_forms");
            $table->foreignId('dfg_id')->constrained('drug_farm_groups')->onDelete('cascade')->comment("Drug Farm Group  ID")->index("drug_farm_groups");
            $table->foreignId('dtg_id')->constrained('drug_ts_groups')->onDelete('cascade')->comment("Drug TS  ID")->index("drug_ts_groups");
            $table->decimal('ref_price', 13, 2)->nullable()->default(0)->index("ref_price");
            $table->string('ref_price_ccy', 5)->nullable()->default("")->index("ref_price_ccy");
            $table->boolean('is_active')->nullable()->default(false);
            $table->boolean('deleted')->nullable()->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drugs');
    }
}
