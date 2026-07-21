<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index("user_id");
            $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade')->index("drug_ids");
            $table->string('serial_number', 20)->nullable()->default("")->index("serial_number");
            $table->date('shelf_life')->nullable()->index("shelf_life");
            $table->date('mode_70_date')->nullable()->index("mode_70_date");
            $table->foreignId('m70d_id')->constrained('distributors')->onDelete('cascade')->comment("mode_70_distributor")->index("m70d_id");
            $table->date('mode_40_date')->nullable()->index("mode_40_date");
            $table->foreignId('m40d_id')->constrained('distributors')->onDelete('cascade')->comment("mode_40_distributor")->index("m40d_id");
            $table->foreignId('sc_id')->constrained('companies')->onDelete('cascade')->comment("Sender Company ID")->index("companies");
            $table->foreignId('trademark_id')->constrained('trademarks')->onDelete('cascade')->comment("trademark ID")->index("trademarks");
            $table->foreignId('manufacturer_id')->constrained('manufacturers')->onDelete('cascade')->comment("manufacturer ID")->index("manufacturers");
            $table->foreignId('mc_id')->constrained('countries')->onDelete('cascade')->comment("manufacturer country ID")->index("countries");
            $table->string('c_price_ccy', 3)->nullable()->default("")->index("c_price_ccy");
            $table->decimal('c_price_ccy_rate', 13, 2)->nullable()->default(0)->index("c_price_ccy_rate");
            $table->decimal('c_price_usd', 13, 2)->nullable()->default(0)->index("c_price_usd");
            $table->decimal('c_price_uzs', 13, 2)->nullable()->default(0)->index("c_price_uzs");
            $table->decimal('c_price_eur', 13, 2)->nullable()->default(0)->index("c_price_eur");
            $table->decimal('c_price_rub', 13, 2)->nullable()->default(0)->index("c_price_rub");
             $table->string('price_ccy', 3)->nullable()->default("")->index("price_ccy");
            $table->decimal('price_ccy_rate', 13, 2)->nullable()->default(0)->index("price_ccy_rate");
            $table->decimal('price_usd', 13, 2)->nullable()->default(0)->index("price_usd");
            $table->decimal('price_uzs', 13, 2)->nullable()->default(0)->index("price_uzs");
            $table->decimal('price_eur', 13, 2)->nullable()->default(0)->index("price_eur");
            $table->decimal('price_rub', 13, 2)->nullable()->default(0)->index("price_rub");
            $table->integer('quantity')->unsigned()->nullable()->default(0)->index("quantity");
            $table->decimal('sum_price_usd', 19, 2)->nullable()->default(0)->index("sum_price_usd");
            $table->decimal('sum_price_uzs', 19, 2)->nullable()->default(0)->index("sum_price_uzs");
            $table->decimal('sum_price_eur', 19, 2)->nullable()->default(0)->index("sum_price_eur");
            $table->decimal('sum_price_rub', 19, 2)->nullable()->default(0)->index("sum_price_rub");
            $table->boolean('is_local')->nullable()->default(false);
            $table->boolean('is_active')->nullable()->default(true);
            $table->boolean('is_deleted')->nullable()->default(false);
            $table->boolean('is_updated')->nullable()->default(false);
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
        Schema::dropIfExists('drug_reports');
    }
}
